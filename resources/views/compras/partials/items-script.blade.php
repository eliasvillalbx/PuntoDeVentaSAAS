<script>
  function formCompra() {
    return {
      productos: [], proveedores: [],
      items: [],
      proveedor_id: '',
      init(prod, prov, prefill) {
        this.productos = prod || [];
        this.proveedores = prov || [];
        this.items = (prefill && prefill.length) ? prefill : [{
          id_producto:'', nombre:'', cantidad:1, costo_unitario:0, descuento:0
        }];
      },
      add(){ this.items.push({ id_producto:'', nombre:'', cantidad:1, costo_unitario:0, descuento:0 }); },
      remove(i){ this.items.splice(i,1); if(!this.items.length) this.add(); },
      syncNombre(i){
        const idp = this.items[i].id_producto;
        const p = this.productos.find(x=>x.id==idp);
        this.items[i].nombre = p ? p.nombre : '';
      },
      totalLinea(it){ const t = (it.cantidad*it.costo_unitario) - (it.descuento||0); return t>0? t:0; },
      subtotal(){ return this.items.reduce((s,it)=> s + this.totalLinea(it), 0); },
      iva(){ return +(this.subtotal()*0.16).toFixed(2); },
      total(){ return +(this.subtotal()+this.iva()).toFixed(2); },
      fmt(n){ return new Intl.NumberFormat('es-MX',{style:'currency', currency:'MXN'}).format(n||0); },
    }
  }
</script>
