<?php

namespace App\Entity;

enum ProductStatus: string
{
    case AVAILABLE = 'disponible';
    case OUT_OF_STOCK = 'en rupture';
    case PREORDER = 'en précommande';
}
