<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../conexion.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario_id'])) {
    // Usar la función redirigir del config que ya maneja la URL correctamente
    redirigir('index.php');
}

// Incluir PhpSpreadsheet
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;

try {
    // Obtener todos los contratos con información completa
    $query = "SELECT c.*, 
              u.nombre as creado_por,
              a.nombre as abogado_asignado
              FROM contratos c
              LEFT JOIN usuarios u ON c.usuario_creacion = u.id
              LEFT JOIN usuarios a ON c.abogado_asignado = a.id
              WHERE c.estado = 'activo'
              ORDER BY c.fecha_creacion DESC";
    
    $result = $mysqli->query($query);
    
    if (!$result) {
        throw new Exception('Error en la consulta: ' . $mysqli->error);
    }
    
    $contratos = [];
    while ($row = $result->fetch_assoc()) {
        $contratos[] = $row;
    }
       
    // Crear nuevo archivo Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Configurar propiedades del documento
    $spreadsheet->getProperties()
        ->setCreator('Sistema de Contratos - Secretaría de Educación Risaralda')
        ->setTitle('Reporte de Contratos')
        ->setSubject('Contratos')
        ->setDescription('Reporte completo de contratos registrados en el sistema');
    
    // Título del documento
    $sheet->setCellValue('A1', 'REPORTE DE CONTRATOS - SECRETARÍA DE EDUCACIÓN RISARALDA');
    $sheet->mergeCells('A1:AM1');
    
    // Estilo del título
    $sheet->getStyle('A1')->applyFromArray([
        'font' => [
            'bold' => true,
            'size' => 16,
            'color' => ['rgb' => 'FFFFFF']
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '2E86AB']
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER
        ]
    ]);
    $sheet->getRowDimension(1)->setRowHeight(30);
    
    // Fecha de generación
    $sheet->setCellValue('A2', 'Fecha de generación: ' . date('d/m/Y H:i:s'));
    $sheet->mergeCells('A2:AM2');
    $sheet->getStyle('A2')->applyFromArray([
        'font' => ['italic' => true, 'size' => 10],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
    ]);
    
    // Encabezados de las columnas (fila 4)
    $encabezados = [
        'A4' => 'ID',
        'B4' => 'Fecha Diligenciamiento',
        'C4' => 'Nombre Completo',
        'D4' => 'Tipo Documento',
        'E4' => 'Número Documento',
        'F4' => 'Lugar Expedición',
        'G4' => 'Fecha Nacimiento',
        'H4' => 'Correo Electrónico',
        'I4' => 'Celular Contacto',
        'J4' => 'Identidad de Género',
        'K4' => 'Grupo Poblacional',
        'L4' => 'Posee Discapacidad',
        'M4' => 'Especifique Discapacidad',
        'N4' => 'Estado Civil',
        'O4' => 'Número Hijos Dependientes',
        'P4' => 'Tiene Hijos Menores',
        'Q4' => 'Cuántos Hijos Menores',
        'R4' => 'Padre/Madre Soltero',
        'S4' => 'Dirección Residencia',
        'T4' => 'Barrio',
        'U4' => 'Municipio Residencia',
        'V4' => 'Nivel de Estudio',
        'W4' => 'Formación Técnica',
        'X4' => 'Formación Tecnológica',
        'Y4' => 'Formación Pregrado',
        'Z4' => 'Formación Posgrado',
        'AA4' => 'Datos Posgrado',
        'AB4' => 'Maestría',
        'AC4' => 'Posee Doctorado',
        'AD4' => 'EPS Afiliado',
        'AE4' => 'Fondo de Pensión',
        'AF4' => 'ARL',
        'AG4' => 'Trabajo Municipio',
        'AH4' => 'Aceptación de Datos',
        'AI4' => 'Estado',
        'AJ4' => 'Creado Por',
        'AK4' => 'Fecha Creación',
        'AL4' => 'Fecha Actualización',
        'AM4' => 'Abogado asignado'
    ];
    
    // Establecer encabezados
    foreach ($encabezados as $celda => $valor) {
        $sheet->setCellValue($celda, $valor);
    }
    
    // Estilo de encabezados
    $sheet->getStyle('A4:AM4')->applyFromArray([
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 11
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '0C7C59']
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
            'wrapText' => true
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000']
            ]
        ]
    ]);
    $sheet->getRowDimension(4)->setRowHeight(30);
    
    // Llenar datos
    $fila = 5;
    foreach ($contratos as $contrato) {
        $sheet->setCellValue('A' . $fila, $contrato['id']);
        $sheet->setCellValue('B' . $fila, formatearFecha($contrato['fecha_diligenciamiento']));
        $sheet->setCellValue('C' . $fila, $contrato['nombre_completo']);
        $sheet->setCellValue('D' . $fila, formatearTexto($contrato['tipo_documento']));
        $sheet->setCellValue('E' . $fila, $contrato['numero_documento']);
        $sheet->setCellValue('F' . $fila, $contrato['lugar_expedicion']);
        $sheet->setCellValue('G' . $fila, formatearFecha($contrato['fecha_nacimiento']));
        $sheet->setCellValue('H' . $fila, $contrato['correo_electronico']);
        $sheet->setCellValue('I' . $fila, $contrato['celular_contacto']);
        $sheet->setCellValue('J' . $fila, formatearTexto($contrato['identidad_genero']));
        $sheet->setCellValue('K' . $fila, formatearTexto($contrato['grupo_poblacional']));
        $sheet->setCellValue('L' . $fila, $contrato['posee_discapacidad'] == 'si' ? 'Sí' : 'No');
        $sheet->setCellValue('M' . $fila, $contrato['especifique_discapacidad'] ?? '');
        $sheet->setCellValue('N' . $fila, formatearTexto($contrato['estado_civil']));
        $sheet->setCellValue('O' . $fila, $contrato['numero_hijos_dependientes']);
        $sheet->setCellValue('P' . $fila, $contrato['tiene_hijos_menores'] == 'si' ? 'Sí' : 'No');
        $sheet->setCellValue('Q' . $fila, $contrato['cuantos_hijos_menores'] ?? '');
        $sheet->setCellValue('R' . $fila, $contrato['padre_madre_soltero'] == 'si' ? 'Sí' : 'No');
        $sheet->setCellValue('S' . $fila, $contrato['direccion_residencia']);
        $sheet->setCellValue('T' . $fila, $contrato['barrio']);
        $sheet->setCellValue('U' . $fila, $contrato['municipio_residencia']);
        $sheet->setCellValue('V' . $fila, formatearTexto($contrato['nivel_estudio']));
        $sheet->setCellValue('W' . $fila, $contrato['formacion_tecnica'] ?? '');
        $sheet->setCellValue('X' . $fila, $contrato['formacion_tecnologica'] ?? '');
        $sheet->setCellValue('Y' . $fila, $contrato['formacion_pregrado'] ?? '');
        $sheet->setCellValue('Z' . $fila, $contrato['formacion_posgrado'] ?? '');
        $sheet->setCellValue('AA' . $fila, formatearTexto($contrato['datos_posgrado'] ?? ''));
        $sheet->setCellValue('AB' . $fila, $contrato['maestria'] ?? '');
        $sheet->setCellValue('AC' . $fila, $contrato['posee_doctorado'] == 'si' ? 'Sí' : 'No');
        $sheet->setCellValue('AD' . $fila, $contrato['eps_afiliado']);
        $sheet->setCellValue('AE' . $fila, formatearTexto($contrato['fondo_pension']));
        $sheet->setCellValue('AF' . $fila, $contrato['arl']);
        $sheet->setCellValue('AG' . $fila, $contrato['trabajo_municipio']);
        $sheet->setCellValue('AH' . $fila, $contrato['aceptacion_datos'] == 'si' ? 'Sí' : 'No');
        $sheet->setCellValue('AI' . $fila, $contrato['estado'] == 'activo' ? 'Activo' : 'Inactivo');
        $sheet->setCellValue('AJ' . $fila, $contrato['creado_por'] ?? '');
        $sheet->setCellValue('AK' . $fila, formatearFecha($contrato['fecha_creacion']));
    $sheet->setCellValue('AL' . $fila, formatearFecha($contrato['fecha_actualizacion']));
    // Nombre del abogado asignado (columna AM)
    $sheet->setCellValue('AM' . $fila, $contrato['abogado_asignado'] ?? '');
        
        // Estilo de filas alternas
        if ($fila % 2 == 0) {
            $sheet->getStyle('A' . $fila . ':AL' . $fila)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F0F0F0']
                ]
            ]);
        }
        
        $fila++;
    }
    
    // Aplicar bordes a todas las celdas con datos
    $ultimaFila = $fila - 1;
    $sheet->getStyle('A4:AM' . $ultimaFila)->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'CCCCCC']
            ]
        ],
        'alignment' => [
            'vertical' => Alignment::VERTICAL_CENTER
        ]
    ]);
    
    // Ajustar ancho de columnas automáticamente
    foreach (range('A', 'Z') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    foreach (['AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM'] as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Congelar paneles (título y encabezados)
    $sheet->freezePane('A5');
    
    // Agregar filtros automáticos
    $sheet->setAutoFilter('A4:AM' . $ultimaFila);
    
    // Total de registros
    $fila++;
    $sheet->setCellValue('A' . $fila, 'TOTAL DE REGISTROS: ' . count($contratos));
    $sheet->mergeCells('A' . $fila . ':AM' . $fila);
    $sheet->getStyle('A' . $fila)->applyFromArray([
        'font' => [
            'bold' => true,
            'size' => 12,
            'color' => ['rgb' => 'FFFFFF']
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '2E86AB']
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER
        ]
    ]);
    $sheet->getRowDimension($fila)->setRowHeight(25);
    
    // Crear el archivo Excel
    $writer = new Xlsx($spreadsheet);
    
    // Configurar headers para descarga
    $filename = 'Contratos_Completo_' . date('Y-m-d_His') . '.xlsx';
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $writer->save('php://output');
    exit();
    
} catch (Exception $e) {
    die('Error al generar el archivo: ' . $e->getMessage());
}
