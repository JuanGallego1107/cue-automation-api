<x-mail::message>
# Documento listo para aprobación

Estimado/a **{{ $coordinatorName }}**,

Le informamos que el siguiente documento ha sido procesado y validado exitosamente
por nuestro sistema, y está listo para su aprobación final.

---

## Detalles del documento

| Campo        | Valor                                        |
|--------------|----------------------------------------------|
| **Archivo**  | {{ $submission->original_filename }}         |
| **Tipo**     | {{ $submission->documentType?->name ?? '—' }} |
| **Asignatura** | {{ $submission->subject?->name ?? '—' }}   |
| **Período**  | {{ $submission->period?->name ?? '—' }}      |
| **Fecha**    | {{ $submission->created_at?->format('d/m/Y H:i') }} |

---

✅ El documento ha pasado todas las validaciones automáticas y no presenta correcciones críticas.

<x-mail::button :url="$approvalUrl" color="success">
Confirmar Aprobación
</x-mail::button>

Una vez que confirme la aprobación, el documento será subido automáticamente a Google Drive
en la carpeta correspondiente al programa, período y tipo de documento.

> **Nota:** Si el botón no funciona, copie y pegue el siguiente enlace en su navegador:
> {{ $approvalUrl }}

---

Atentamente,
**Sistema CUE Automation**
</x-mail::message>
