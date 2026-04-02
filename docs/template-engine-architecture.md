# Hybrid Template Engine (Enterprise)

## Objetivo
Preparar una arquitectura corporativa para soportar plantillas de múltiples empresas en `xlsx`, `docx` y `pdf` sin romper flujos actuales.

## Estado actual
- Implementación **no disruptiva**: solo infraestructura.
- No está conectada aún al flujo de actas.
- `config/template_engine.php` queda desactivado por defecto (`enabled=false`).

## Capas del motor
1. Auto-fill inteligente:
   - Marcadores explícitos (`{{field_key}}`).
   - Heurísticas por etiquetas visibles (especialmente en Excel).
2. Mapeo visual asistido:
   - Cuando la confianza no es suficiente, el usuario mapea y guarda.
3. Perfil por empresa:
   - Versionado de plantilla y métricas de precisión por tipo de documento.

## Adapters iniciales
- `XlsxTemplateAdapter`: mayor cobertura (tablas + etiquetas + marcadores).
- `DocxTemplateAdapter`: robusto con marcadores.
- `PdfFillableTemplateAdapter`: orientado a AcroForm (PDF rellenable).

## Activación gradual recomendada
1. Encender feature flag en entorno de pruebas.
2. Conectar manager solo para diagnóstico (sin escritura).
3. Habilitar auto-fill por tipo de documento:
   - TI Entrega/Devolución
   - Préstamos
   - Otros Activos
   - Bajas
4. Liberar mapeo visual por empresa.

## No incluye en esta fase
- OCR para PDF plano/escaneado.
- Reentrenamiento ML por layout.
- Alteración de rutas o UX actual.

