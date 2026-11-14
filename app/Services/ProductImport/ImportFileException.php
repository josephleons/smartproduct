<?php

namespace App\Services\ProductImport;

use RuntimeException;

/** An uploaded import file that cannot be used; the message is safe to show to the seller. */
class ImportFileException extends RuntimeException {}
