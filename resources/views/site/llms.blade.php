# TipiDV

> Tipificador visual de PDFs en lote para Windows. Clasifica páginas, exporta un archivo por tipo y organiza carpetas por factura o radicado. Plantilla MinSalud (FEV, HEV, EPI, RIPS) incluida; tipos personalizables.

TipiDV es software de escritorio para digitalización y tipificación de documentos PDF. Caso principal: facturación hospitalaria en Colombia según normativa del Ministerio de Salud (RIPS como soporte de FEV). También sirve para archivo, contabilidad y legal con tipos definidos por el usuario.

## Enlaces principales

- Inicio: {{ url('/') }}
- Comprar licencia: {{ url('/comprar') }}
- Descargar instalador Windows: {{ route('site.download') }}
- Contacto: {{ config('marketing.contact_email') }}

## Producto

- Nombre: {{ config('marketing.site_name', 'TipiDV') }}
- Plataforma: Windows 10/11 (64 bits)
- Licencia: por equipo (1 a {{ config('marketing.max_license_quantity', 50) }} PCs por clave TDV)
- Pago: Wompi (COP)
- Autor: {{ config('marketing.author') }}

## Palabras clave

tipificador PDF, clasificar PDF, soportes MinSalud, FEV, HEV, EPI, RIPS, factura electrónica salud, hospital, IPS, Colombia

## Sitemap

{{ url('/sitemap.xml') }}
