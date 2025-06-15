<?php

namespace App\Common;

use Symfony\Component\HttpFoundation\Request;

class DataTablesAjaxRequest
{
    private Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getStart(): int
    {
        return (int) $this->getRequestData()['start'] ?? 0;
    }

    public function getLength(): int
    {
        return (int) $this->getRequestData()['length'] ?? 10;
    }

    public function getSearchText(): string
    {
        return (string) ($this->getRequestData()['search']['value'] ?? '');
    }

    public function getPageIndex(): int
    {
        $length = $this->getLength();
        return $length > 0 ? (int) ($this->getStart() / $length) + 1 : 1;
    }

    public function getPageSize(): int
    {
        $length = $this->getLength();
        return $length === 0 ? 10 : $length;
    }

    public static function getEmptyResult(): array
    {
        return [
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
        ];
    }

    public function getSortText(array $columnNames): string
    {
        $sortTextParts = [];
        $data = $this->getRequestData();

        for ($i = 0; ; $i++) {
            if (!isset($data["order"][$i]["column"])) {
                break;
            }

            $columnIndex = (int) $data["order"][$i]["column"];
            $direction = strtolower($data["order"][$i]["dir"] ?? 'asc');

            if (!isset($columnNames[$columnIndex])) {
                continue;
            }

            $direction = $direction === 'desc' ? 'desc' : 'asc'; // default to asc if invalid
            $sortTextParts[] = "{$columnNames[$columnIndex]} $direction";
        }

        return implode(', ', $sortTextParts);
    }

    public function getRequestData(): array
    {
        if ($this->request->isMethod('GET')) {
            return $this->request->query->all();
        } elseif ($this->request->isMethod('POST')) {
            return $this->request->request->all();
        }

        throw new \RuntimeException('HTTP method not supported, use GET or POST');
    }
}
