<?php

namespace App\Imports;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class RowRangeReadFilter implements IReadFilter
{
    /**
     * @param array<string> | null $columns
     */
    public function __construct(
        private readonly int $startRow,
        private readonly int $endRow,
        private readonly ?array $columns = null,
    ) {
    }

    public function readCell($columnAddress, $row, $worksheetName = ''): bool
    {
        if ($row < $this->startRow || $row > $this->endRow) {
            return false;
        }

        if ($this->columns === null) {
            return true;
        }

        return in_array($columnAddress, $this->columns, true);
    }
}
