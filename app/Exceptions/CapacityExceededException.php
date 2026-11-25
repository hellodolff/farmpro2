<?php

namespace App\Exceptions;

use RuntimeException;

/** Dilempar saat batch melebihi kapasitas kandang dan belum dikonfirmasi pengguna. */
class CapacityExceededException extends RuntimeException
{
}
