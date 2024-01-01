<?php

namespace App;

use Symfony\Component\DomCrawler\Crawler;

class ParseHtml
{

    private array $array = [];
    private string $htmlTable = '';


    public function __construct(
        private readonly string $html
    )
    {
    }

    public function getHtmlTable(): string
    {
        return $this->htmlTable;
    }

    public function parseStudentInfo(): array
    {
        $studentInfo = [];
//        $studentInfo['id'] = $this->parseStudentId();
        $studentInfo['image'] = $this->parseStudentImage();
        $studentInfo['name'] = $this->parseStudentName();
        return $studentInfo;
    }

    public function parseStudentId(): ?int
    {
        $html = $this->html;
        $crawler = new Crawler($html);
        $scriptContent = $crawler->filterXPath('//script[contains(text(), "var id_student")]')->text();
        preg_match('/var id_student = (\d+);/', $scriptContent, $matches);
        return (int)$matches[1] ?? null;
    }

    private function parseStudentImage(): ?string
    {
        $html = $this->html;
        $crawler = new Crawler($html);
        return $crawler->filter('img')->attr('src');
    }

    private function parseStudentName(): ?string
    {
        return $this->parseSimpleTables()[0][2];
    }

    public function parseSubjectsTable(): static
    {
        $this->array = $this->parseSimpleTables();
        return $this;
    }

    public function parseExamsTable(): static
    {
        $this->array = $this->parseSimpleTables();
        return $this;
    }

    public function arrayToHtmlTable(array $theadTexts): static
    {
        $array = $this->array;
        for ($i = 0; $i < count($array); $i++) {
            $array[$i] = array_combine($theadTexts, $array[$i]);
        }

        $htmlTable = '<table style="border-collapse: collapse; width: 100%; border: 1px solid #ddd;">';
        $htmlTable .= '<thead><tr>';

        if (!empty($array)) {
            foreach (array_keys($array[0]) as $columns) {
                $htmlTable .= '<th style="border: 3px solid #ddd; background: #4b5563; color: aliceblue; padding: 5px;">' . $columns . '</th>';
            }
        }

        $htmlTable .= '</tr></thead><tbody>';
        foreach ($array as $rows) {
            $htmlTable .= '<tr>';
            foreach ($rows as $cell) {
                $htmlTable .= '<td style="border: 3px solid #ddd; padding: 5px;">' . $cell . '</td>';
            }
            $htmlTable .= '</tr>';
        }
        $htmlTable .= '</tbody></table>';
        $this->htmlTable = $htmlTable;
        return $this;
    }

    private function parseSimpleTables()
    {
        $crawler = new Crawler($this->html);
        $tableRows = $crawler->filter('table tbody tr');

        $tableRows->each(function (Crawler $row, $i) use (&$result) {
            $result[$i] = $row->filter('td')->each(function (Crawler $cell) {
                return $cell->text();
            });
        });

        return $result;
    }
}