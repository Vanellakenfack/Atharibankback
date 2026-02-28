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
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class BalanceGeneraleExport implements FromView, ShouldAutoSize, WithTitle, WithStyles, WithColumnFormatting
{
    protected $data;
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
        return view('reports.balance.balance_generale', [
            'donnees'    => $this->data['donnees'],
            'stats'      => $this->data['statistiques'],
            'dateDebut'  => $this->dateDebut,
            'dateFin'    => $this->dateFin,
            'agence_nom' => $this->agence_nom
        ]);
    }

    public function title(): string
    {
        return 'Balance Générale';
    }

    /**
     * Formate les colonnes C à H en nombres avec séparateurs de milliers
     */
    public function columnFormats(): array
    {
        return [
            'C:H' => '#,##0', 
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $rangeAll = 'A1:H' . $highestRow;

        // 1. Mise en page globale
        $sheet->getStyle($rangeAll)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        // 2. Style du titre (Ligne 1)
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // 3. Style des en-têtes de colonnes (Th de la table)
        // On suppose que les en-têtes sont autour de la ligne 6 après les infos d'agence
        $headerRange = 'A6:H7'; 
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '444444'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // 4. Bordures pour toute la zone de données
        $sheet->getStyle('A6:H' . $highestRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '888888'],
                ],
            ],
        ]);

        // 5. Stylisation dynamique des lignes (Classes et Totaux)
        for ($i = 8; $i <= $highestRow; $i++) {
            $cellA = (string)$sheet->getCell('A' . $i)->getValue();
            $cellB = (string)$sheet->getCell('B' . $i)->getValue();

            // Ligne de Classe (ex: CLASSE 1) - Basé sur votre structure Blade
            if (str_contains($cellA, 'CLASSE') || str_contains($cellB, 'CLASSE')) {
                $sheet->getStyle('A' . $i . ':H' . $i)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D9EAD3'], // Vert très clair pour les classes
                    ],
                ]);
            }

            // Ligne de Total de groupe
            if (str_contains($cellA, 'TOTAL') || str_contains($cellB, 'TOTAL')) {
                $sheet->getStyle('A' . $i . ':H' . $i)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F3F3F3'],
                    ],
                ]);
            }

            // Ligne de Total Général (Récapitulatif)
            if (str_contains($cellA, 'RÉCAPITULATIF') || str_contains($cellB, 'RÉCAPITULATIF')) {
                $sheet->getStyle('A' . $i . ':H' . $i)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '212529'],
                    ],
                ]);
            }
        }

        return [];
    }
}