<?php
declare(strict_types=1);

namespace App\Support;

enum Proficiency: string
{
    case None = 'none';
    case Beginner = 'beginner';
    case Intermediate = 'intermediate';
    case Advanced = 'advanced';
}
