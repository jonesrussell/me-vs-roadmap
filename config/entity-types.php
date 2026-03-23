<?php

declare(strict_types=1);

/**
 * Application-specific entity types.
 *
 * Return an array of EntityType instances to register additional entity
 * types beyond those provided by Waaseyaa packages.
 *
 * Example:
 *   return [
 *       new \Waaseyaa\Entity\EntityType(
 *           id: 'product',
 *           label: 'Product',
 *           class: \App\Entity\Product::class,
 *           keys: ['id' => 'id', 'uuid' => 'uuid', 'label' => 'name'],
 *       ),
 *   ];
 */

return [
    new \Waaseyaa\Entity\EntityType(id: 'developer', label: 'Developer', class: \App\Entity\Developer::class, keys: ['id' => 'id', 'uuid' => 'uuid', 'label' => 'display_name']),
    new \Waaseyaa\Entity\EntityType(id: 'scan', label: 'Scan', class: \App\Entity\Scan::class, keys: ['id' => 'id', 'uuid' => 'uuid']),
    new \Waaseyaa\Entity\EntityType(id: 'roadmap_path', label: 'Roadmap Path', class: \App\Entity\RoadmapPath::class, keys: ['id' => 'id', 'uuid' => 'uuid', 'label' => 'name']),
    new \Waaseyaa\Entity\EntityType(id: 'roadmap_skill', label: 'Roadmap Skill', class: \App\Entity\RoadmapSkill::class, keys: ['id' => 'id', 'uuid' => 'uuid', 'label' => 'name']),
    new \Waaseyaa\Entity\EntityType(id: 'skill_assessment', label: 'Skill Assessment', class: \App\Entity\SkillAssessment::class, keys: ['id' => 'id', 'uuid' => 'uuid']),
    new \Waaseyaa\Entity\EntityType(id: 'skill_evidence', label: 'Skill Evidence', class: \App\Entity\SkillEvidence::class, keys: ['id' => 'id', 'uuid' => 'uuid']),
];
