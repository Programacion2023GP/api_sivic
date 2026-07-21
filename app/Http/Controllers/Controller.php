<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * 📤 Subir imagen al servidor local (storage/app/public)
     * 
     * @param \Illuminate\Http\UploadedFile $image
     * @param string $curp
     * @param string $dirPath   Ej: "presidencia/SIVIC/multas"
     * @param string $imgName   Ej: "CURP123" o "car_CURP123"
     * @return string|null      Nombre del archivo guardado o null en error
     */
    public function ImgUpload($image, $curp, $dirPath, $imgName)
    {
        try {
            // Validar imagen
            if (!$image || !$image->isValid()) {
                \Log::error('❌ ImgUpload: Imagen inválida');
                return null;
            }

            // Generar nombre único
            $extension = $image->getClientOriginalExtension();
            $filename = $imgName . '_' . time() . '.' . $extension;

            // Construir ruta relativa dentro de storage/app/public
            $relativePath = $dirPath . '/' . $curp;

            // Guardar en disco 'public'
            $storedPath = $image->storeAs(
                $relativePath,
                $filename,
                'public'
            );

            if (!$storedPath) {
                \Log::error('❌ ImgUpload: Fallo al guardar archivo');
                return null;
            }

            \Log::info("✅ Imagen guardada localmente: {$storedPath}");

            // Retornar solo el nombre del archivo (para construir URL después)
            return $filename;
        } catch (\Exception $e) {
            \Log::error('❌ ImgUpload Exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Funci├│n auxiliar para subir al microservicio con los par├ímetros espec├¡ficos
     */
    private function uploadToMicroservice($file, $destination, $dir, $filename)
    {
        try {
            $client = new \GuzzleHttp\Client([
                'verify' => false, // Disable SSL verification
            ]);

            $response = $client->request('POST', 'https://api.gpcenter.gomezpalacio.gob.mx/api/smImgUpload', [
                'multipart' => [
                    [
                        'name'     => 'Firma_Director',
                        'contents' => fopen($file->getPathname(), 'r'),
                        'filename' => $filename,
                    ],
                    [
                        'name' => 'dirDestination',
                        'contents' => $destination,
                    ],
                    [
                        'name' => 'dirPath',
                        'contents' => $dir,
                    ],
                    [
                        'name' => 'imgName',
                        'contents' => $filename,
                    ],
                    [
                        'name' => 'requestFileName',
                        'contents' => 'Firma_Director',
                    ],
                ],
                'timeout' => 30, // Add timeout
                'connect_timeout' => 10,
            ]);

            // Check response status
            if ($response->getStatusCode() !== 200) {
                throw new \Exception('Error al subir la imagen: ' );
            }

            return $response;
        } catch (\Exception $e) {
            throw new \Exception('Error en uploadToMicroservice: ' );
        }
    }
}
