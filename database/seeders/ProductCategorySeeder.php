<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ProductCategory;
use App\Models\Company;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        $categories = [
            [
                'name' => 'Tuberías',
                'description' => 'Tuberías de diferentes materiales y diámetros para instalaciones de agua',
                'config' => [
                    'attributes' => [
                        'tipo' => ['PVC', 'Hierro', 'Cobre', 'Polietileno'],
                        'diametro' => ['1/2"', '3/4"', '1"', '1 1/4"', '1 1/2"', '2"', '3"', '4"', '6"'],
                        'material' => ['PVC', 'PVC reforzado', 'Hierro galvanizado', 'Cobre', 'HDPE'],
                        'longitud' => ['3m', '6m', '12m'],
                        'presion' => ['6kg/cm²', '10kg/cm²', '16kg/cm²']
                    ]
                ]
            ],
            [
                'name' => 'Conexiones',
                'description' => 'Accesorios y conexiones para sistemas de tuberías',
                'config' => [
                    'attributes' => [
                        'tipo' => ['Codo', 'Te', 'Reducción', 'Unión', 'Tapón', 'Brida'],
                        'tamaño' => ['1/2"', '3/4"', '1"', '1 1/4"', '1 1/2"', '2"', '3"', '4"'],
                        'material' => ['PVC', 'Bronce', 'Hierro', 'Acero inoxidable'],
                        'aplicacion' => ['Agua fría', 'Agua caliente', 'Aguas servidas', 'Gas'],
                        'rosca' => ['NPT', 'BSP', 'Soldable']
                    ]
                ]
            ],
            [
                'name' => 'Medidores',
                'description' => 'Medidores de agua para control de consumo',
                'config' => [
                    'attributes' => [
                        'marca' => ['Sensus', 'Elster', 'Itron', 'Badger', 'Neptune'],
                        'modelo' => ['Básico', 'Digital', 'Smart', 'Ultrasónico'],
                        'rango' => ['1/2"', '3/4"', '1"', '1 1/2"', '2"'],
                        'precision' => ['Clase A', 'Clase B', 'Clase C'],
                        'tipo_lectura' => ['Mecánico', 'Digital', 'Remoto', 'AMR', 'AMI']
                    ]
                ]
            ]
        ];

        foreach ($companies as $company) {
            foreach ($categories as $categoryData) {
                ProductCategory::firstOrCreate([
                    'company_id' => $company->id,
                    'name' => $categoryData['name']
                ], [
                    'description' => $categoryData['description'],
                    'config' => $categoryData['config'],
                    'is_active' => true
                ]);
            }
        }
    }
}
