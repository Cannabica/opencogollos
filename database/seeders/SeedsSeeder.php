<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SeedsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Leer el archivo CSV directamente
        $csvFile = fopen(database_path('seeders/seeds.csv'), 'r');
        
        // Leer la primera línea para obtener los encabezados y limpiarlos
        $headers = array_map('trim', fgetcsv($csvFile));
        
        // Mostrar los encabezados para debug
        $this->command->info("Headers encontrados: " . implode(', ', $headers));

        $seedTypeMapping = [
            'Feminizada fotoperiódica' => 'Fotoperiodica feminizada',
            'Regular' => 'Fotoperiodica regular',
            'Automática' => 'Automatica',
        ];

        // Leer el resto de las líneas
        while (($row = fgetcsv($csvFile)) !== false) {
            try {
                // Limpiar los valores y crear un array asociativo
                $row = array_map('trim', $row);
                $data = array_combine($headers, $row);
                
                // Debug
                $this->command->info("Procesando fila: " . json_encode($data));

                // Transformar THC
                $thc = $data['THC (%)'];
                if ($thc === 'Alto') {
                    $thc = '20'; // valor por defecto para "Alto"
                } elseif (strpos($thc, '-') !== false) {
                    // Si es un rango (ej: "17-20"), tomar el promedio
                    $range = explode('-', str_replace('%', '', $thc));
                    $thc = (floatval($range[0]) + floatval($range[1])) / 2;
                } else {
                    $thc = str_replace(['%', '<'], '', $thc);
                }

                // Transformar CBD
                $cbd = $data['CBD (%)'];
                if ($cbd === 'Bajo') {
                    $cbd = '0.5'; // valor por defecto para "Bajo"
                } elseif (strpos($cbd, '-') !== false) {
                    $range = explode('-', str_replace('%', '', $cbd));
                    $cbd = (floatval($range[0]) + floatval($range[1])) / 2;
                } else {
                    $cbd = str_replace(['%', '<'], '', $cbd);
                }

                // Transformar tiempo de floración
                $flowering_time = $data['Tiempo de flora (semanas)'];
                if ($flowering_time === 'No especificado') {
                    $flowering_time = null;
                } elseif (strpos($flowering_time, '-') !== false) {
                    $range = explode('-', $flowering_time);
                    $flowering_time = (floatval($range[0]) + floatval($range[1])) / 2;
                } else {
                    $flowering_time = floatval($flowering_time);
                }

                // Determinar si está aprobado por INASE
                $aprobado_inase = str_contains($data['Registro INASE o banco'], 'INASE') || 
                                 str_contains($data['Registro INASE o banco'], 'fiscalizada');

                DB::table('seeds')->insert([
                    'name' => trim($data['Nombre']),
                    'tenant_id' => null,
                    'seed_type' => $seedTypeMapping[trim($data['Tipo de semilla'])] ?? trim($data['Tipo de semilla']),
                    'flowering_time' => $flowering_time,
                    'ratio_thc' => floatval($thc),
                    'ratio_cbd' => floatval($cbd),
                    'aprobado_inase' => $aprobado_inase,
                    'provider' => trim($data['Banco/Proveedor']),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            } catch (\Exception $e) {
                $this->command->error("Error procesando fila: " . $e->getMessage());
                $this->command->error("Data: " . json_encode($data));
                continue;
            }
        }

        fclose($csvFile);
    }
}
