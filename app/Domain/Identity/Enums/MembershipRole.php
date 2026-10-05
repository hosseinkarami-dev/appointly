<?php

namespace App\Domain\Identity\Enums;

enum MembershipRole: string
{
    case Owner = 'owner';
    case Staff = 'staff';
}
