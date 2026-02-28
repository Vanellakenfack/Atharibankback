<?php

namespace App\Exports\Comptabilite\Balance;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class BalanceAuxiliaireExport implements FromView, ShouldAutoSize, WithTitle, WithStyles, WithColumnFormatting
{
    protected $data; // Contiendra tout le résultat du service
    protected $dateDebut;
    protected $dateFin;
    protected $agence_nom;

    public function __construct($data, $dateDebut, $dateFin, $agence_nom = null)
    {
        $this->data = $data;
        $this->dateDebut = $dateDebut;
        $this->dateFin = $dateFin;
        $this->agence_nom = $agence_nom;
    }

    public function view(): View
    {
        // On s'assure de passer 'donnees' et 'stats' pour que la vue Blade 
        // partagée avec le PDF fonctionne sans modification.
        return view('reports.balance.balance_auxiliaire', [
            'donnees'    => $this->data['chapitres'] ?? [],
            'stats'      => $this->data['statistiques'] ?? [],
            'dateDebut'  => $this->dateDebut,
            'dateFin'    => $this->dateFin,
            'agence_nom' => $this->agence_nom ?? ($this->data['agence_nom'] ?? 'TOUTES LES AGENCES'),
            'isExcel'    => true // Flag pour masquer le CSS du PDF dans la vue
        ]);
    }

    public function title(): string
    {
        return 'Balance Auxiliaire';
    }

    public function columnFormats(): array
    {
        return [
            'C:H' => '#,##0', // Format monétaire sans décimales
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();

        // 1. Alignement global et formatage montants
        $sheet->getStyle('A1:H' . $highestRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('C6:H' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // 2. Fusion et style du titre principal
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setSize(16)->setBold(true);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // 3. Style des en-têtes de colonnes
        $headerRange = 'A6:H7'; 
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '343A40'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // 4. Bordures
        $sheet->getStyle('A6:H' . $highestRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // 5. Stylisation dynamique des lignes (Optimisée)
        for ($row = 8; $row <= $highestRow; $row++) {
            $valA = (string)$sheet->getCell('A' . $row)->getValue();
            $valB = (string)$sheet->getCell('B' . $row)->getValue();

            // Lignes de Chapitre
            if (str_contains($valA, 'CHAPITRE')) {
                $sheet->getStyle("A$row:H$row")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E9ECEF']],
                ]);
            }

            // Lignes de Totaux (Chapitre ou Général)
            if (str_contains($valA, 'TOTAL') || str_contains($valB, 'TOTAL')) {
                $isGeneral = str_contains($valA, 'GÉNÉRAL') || str_contains($valB, 'GÉNÉRAL');
                
                $sheet->getStyle("A$row:H$row")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => $isGeneral ? 'FFFFFF' : '000000']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID, 
                        'startColor' => ['rgb' => $isGeneral ? '000000' : 'F8F9FA']
                    ],
                ]);
            }
        }

        return [];
    }
}