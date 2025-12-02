<?php

namespace App\Enum;

enum OrderStatus: string
{
    case EN_PREPARATION = 'en préparation';
    case EXPEDIEE = 'expédiée';
    case LIVREE = 'livrée';
    case ANNULEE = 'annulée';
}
