<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use ZipArchive;
use File;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;


class BackupController extends Controller
{
    private $backupFolder = 'Laravel';

    public function index()
    {
        $files = Storage::files($this->backupFolder);
        $backups = [];

        foreach ($files as $file) {
            if (str_ends_with($file, '.zip')) {
                $backups[] = [
                    'file_name'     => basename($file),
                    'file_path'     => $file,
                    'file_size'     => $this->formatBytes(Storage::size($file)),
                    'last_modified' => Carbon::createFromTimestamp(Storage::lastModified($file))->format('d/m/Y H:i'),
                    'ago'           => Carbon::createFromTimestamp(Storage::lastModified($file))->diffForHumans(),
                ];
            }
        }

        return view('backups.index', compact('backups'));
    }

   public function create() 
   {
        dispatch(function () 
        {
            Artisan::call('backup:run', ['--only-db' => true]); 
        }); return back()->with('success', 'El respaldo se está generando en segundo plano.'); 
    }

    public function download(Request $request)
    {
        return Storage::download($request->path);
    }

    public function delete(Request $request)
    {
        Storage::delete($request->path);
        return back()->with('success', 'Respaldo eliminado correctamente.');
    }

    public function restore(Request $request)
    {
        $path = $request->input('path');
        $disk = Storage::disk('local');

        if (!$disk->exists($path)) return redirect()->back()->with('error', 'Archivo no encontrado.');

        try {
            set_time_limit(0); 
            $fullPath = $disk->path($path);
            $tempPath = storage_path('app/restore-temp/' . now()->timestamp);
            
            // 1. Descomprimir
            $zip = new ZipArchive;
            if ($zip->open($fullPath) === TRUE) {
                $zip->extractTo($tempPath);
                $zip->close();
            } else {
                throw new \Exception("Error al descomprimir ZIP.");
            }

            // 2. Buscar SQL
            $sqlFile = collect(File::allFiles($tempPath))->first(fn($file) => $file->getExtension() === 'sql')?->getRealPath();

            if (!$sqlFile) {
                File::deleteDirectory($tempPath);
                throw new \Exception("No hay archivo .sql en el respaldo.");
            }

            // 3. Credenciales
            $dbUser = env('DB_USERNAME');
            $dbPass = env('DB_PASSWORD');
            $dbName = env('DB_DATABASE');
            $dbHost = '127.0.0.1'; // IP Fija para evitar error localhost

            // 4. Buscar ejecutable mysql
            $dumpPath = config('database.connections.mysql.dump.dump_binary_path');
            $dumpPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dumpPath);
            $mysqlExe = rtrim($dumpPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mysql.exe';

            if (!file_exists($mysqlExe)) {
                 $mysqlExe = 'mysql'; // Intento global si falla la ruta
            }

            // 5. Comando (Sin --protocol=tcp para probar compatibilidad)
            $command = sprintf(
                '"%s" -u "%s" %s -h %s "%s" < "%s"',
                $mysqlExe,
                $dbUser,
                $dbPass ? '-p"'.$dbPass.'"' : '', // Solo pone -p si hay pass
                $dbHost,
                $dbName,
                $sqlFile
            );

            // 6. Ejecutar
            $output = [];
            $resultCode = null;
            exec($command, $output, $resultCode);

            File::deleteDirectory($tempPath);

            if ($resultCode !== 0) {
                throw new \Exception("El comando mysql falló con código $resultCode.");
            }

            return redirect()->back()->with('success', 'Base de datos restaurada.');

        } catch (\Exception $e) {
            if (isset($tempPath)) File::deleteDirectory($tempPath);
            return redirect()->back()->with('error', 'Fallo restauración: ' . $e->getMessage());
        }
    }

    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
