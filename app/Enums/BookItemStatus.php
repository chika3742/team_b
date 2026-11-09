<?php

namespace App\Enums;

enum BookItemStatus: string
{
    case Available = 'available';
    case OnLoan = 'on_loan';
}
