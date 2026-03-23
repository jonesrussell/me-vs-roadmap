<?php
declare(strict_types=1);

namespace App\Support;

enum EvidenceType: string
{
    case LanguageUsage = 'language_usage';
    case ConfigFile = 'config_file';
    case Dependency = 'dependency';
    case CiWorkflow = 'ci_workflow';
    case TestPresence = 'test_presence';
    case CommitPattern = 'commit_pattern';
}
