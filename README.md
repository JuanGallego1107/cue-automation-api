# C.U.E. Alexander von Humboldt - Automatización de Planeadores y Notas (Backend API)

<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="300" alt="Laravel Logo">
</p>

Este repositorio contiene la API del núcleo del sistema para la verificación, validación y almacenamiento automatizado de planeadores académicos y registros de notas. 

El backend actúa como el orquestador principal: gestiona la lógica de negocio, se conecta de forma asíncrona con servicios de inteligencia artificial y almacenamiento, y expone los endpoints REST requeridos por la interfaz web en Vue.

---

## 🛠️ Arquitectura y Tecnologías

* **Framework Principal:** Laravel 11 / API Mode
* **Base de Datos:** MySQL (Persistencia de documentos, usuarios, logs y auditorías)
* **Procesamiento Asíncrono:** Laravel Queue Workers (Driver: `database`)
* **Servicios Externos Integrados:**
  * **OCR Engine:** Azure Document Intelligence (Capa Gratuita F0) para extracción estructurada de texto.
  * **Agente de IA:** Google AI Studio (Gemini 2.5 Flash API) para el análisis semántico y validación de contenidos contra el microcurrículo.
  * **Almacenamiento:** Google Drive API para la organización de archivos aprobados.

---

## 🚀 Requisitos Previos

* **PHP** (Versión 8.2 o superior recomendada)
* **Composer**
* **MySQL Server**

---

## 💻 Configuración e Instalación Local

Sigue estos pasos para clonar el repositorio y levantar el entorno de desarrollo básico del Sprint 1:

### 1. Clonar el repositorio
```bash
git clone [https://github.com/JuanGallego1107/cue-automation-api.git](https://github.com/JuanGallego1107/cue-automation-api.git)
cd cue-automation-api