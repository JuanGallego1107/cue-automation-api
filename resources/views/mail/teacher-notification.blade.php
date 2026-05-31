<x-mail::message>
# Revisión de documento — Se requieren correcciones

Estimado/a **{{ $validationResult['teacher']['name'] ?? 'Docente' }}**,

Le informamos que el documento **{{ $submission->original_filename }}** ha sido analizado
automáticamente por nuestro sistema y se han detectado aspectos que requieren su atención.

---

## Resumen del análisis

{{ $validationResult['summary'] }}

---

@if (!empty($issues['critico']))
## ⛔ Problemas Críticos (requieren corrección obligatoria)

@foreach ($issues['critico'] as $issue)
**Campo:** `{{ $issue['field'] }}`
**Descripción:** {{ $issue['description'] }}
**Recomendación:** {{ $issue['recommendation'] }}

---
@endforeach
@endif

@if (!empty($issues['advertencia']))
## ⚠️ Advertencias (revisar con atención)

@foreach ($issues['advertencia'] as $issue)
**Campo:** `{{ $issue['field'] }}`
**Descripción:** {{ $issue['description'] }}
**Recomendación:** {{ $issue['recommendation'] }}

---
@endforeach
@endif

@if (!empty($issues['informativo']))
## ℹ️ Información adicional

@foreach ($issues['informativo'] as $issue)
**Campo:** `{{ $issue['field'] }}`
**Descripción:** {{ $issue['description'] }}
**Recomendación:** {{ $issue['recommendation'] }}

---
@endforeach
@endif

## Recomendaciones generales

{{ $validationResult['recommendations'] }}

---

*Este análisis fue realizado el **{{ $analyzedAt }}** por el sistema automatizado de revisión
de documentos académicos. Por favor, corrija el documento y vuelva a enviarlo a través del
coordinador responsable.*

Atentamente,
**Sistema CUE Automation**
</x-mail::message>
