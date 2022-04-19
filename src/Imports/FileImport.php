<?php

namespace Fournodes\ImportOperation\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

class FileImport implements ToArray, WithCalculatedFormulas
{
    public function array(array $row)
    {
    }
}
