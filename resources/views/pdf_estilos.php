<?php /* Estilos compartidos por los reportes en PDF (dompdf solo entiende CSS simple) */ ?>
<style>
    @page { margin: 28px 32px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #3B2610; }
    h1 { font-size: 20px; margin: 0 0 4px; color: #4F2F0E; }
    h2 { font-size: 13px; margin: 22px 0 8px; color: #4F2F0E; border-bottom: 1px solid #E8D9C8; padding-bottom: 4px; }
    .muted { color: #8A7058; }
    .cabecera td { vertical-align: top; }
    .kpis { width: 100%; border-collapse: separate; border-spacing: 6px; margin: 14px -6px 0; }
    .kpis td { background: #F7F1EA; border-radius: 6px; padding: 10px; width: 25%; }
    .kpis .valor { font-size: 15px; font-weight: bold; color: #4F2F0E; margin-top: 4px; }
    .barra { background: #EFE5D9; height: 10px; border-radius: 5px; }
    .barra div { background: #6E4620; height: 10px; border-radius: 5px; }
    .alerta { background: #FDF0EE; color: #8B2A1A; padding: 8px 10px; border-radius: 6px; margin-top: 12px; font-weight: bold; }
    table.datos { width: 100%; border-collapse: collapse; }
    table.datos th { text-align: left; background: #F7F1EA; padding: 6px; font-size: 10px; text-transform: uppercase; color: #7A5C3E; }
    table.datos td { padding: 6px; border-bottom: 1px solid #F0E6DA; }
    .monto { text-align: right; white-space: nowrap; }
    .pie { margin-top: 24px; font-size: 9px; color: #8A7058; text-align: center; }
</style>
