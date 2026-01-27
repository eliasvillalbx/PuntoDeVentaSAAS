<?php

namespace App\Mail;

use App\Models\Venta;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class VentaCfdiDemoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Venta $venta,
        public string $emailCliente,
        public string $rfcCliente,
        public string $xmlDisk,
        public string $xmlPath,
        public string $pdfDisk,
        public string $pdfPath,
    ) {}

    public function build()
    {
        $subject = "Factura DEMO (sin timbrar) - Venta #{$this->venta->id}";

        $mail = $this->subject($subject)
            ->view('emails.venta_cfdi_demo', [
                'venta' => $this->venta,
                'emailCliente' => $this->emailCliente,
                'rfcCliente' => $this->rfcCliente,
            ]);

        // Adjuntar XML
        if (Storage::disk($this->xmlDisk)->exists($this->xmlPath)) {
            $mail->attachData(
                Storage::disk($this->xmlDisk)->get($this->xmlPath),
                basename($this->xmlPath),
                ['mime' => 'application/xml']
            );
        }

        // Adjuntar PDF
        if (Storage::disk($this->pdfDisk)->exists($this->pdfPath)) {
            $mail->attachData(
                Storage::disk($this->pdfDisk)->get($this->pdfPath),
                basename($this->pdfPath),
                ['mime' => 'application/pdf']
            );
        }

        return $mail;
    }
}
