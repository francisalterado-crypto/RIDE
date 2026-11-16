<?php

declare(strict_types=1);

/**
 * Demo accounts for development installs (listed in RIDE-Demo-Accounts.docx).
 * Password for all: password123
 *
 * role: role slug. college: college code (college roles). clusters: cluster names (RIDE coordinators).
 *
 * Seed / refresh with:
 *   c:\xampp\php\php.exe scripts\seed-demo-accounts.php
 */
return [
    // University-wide
    ['email' => 'admin@ride.local', 'role' => 'ride_admin', 'name' => 'Ana Reyes'],
    ['email' => 'vpride@ride.local', 'role' => 'vpride', 'name' => 'Ramon Villanueva'],
    ['email' => 'director.research@ride.local', 'role' => 'director_research', 'name' => 'Liza Mendoza'],
    ['email' => 'director.extension@ride.local', 'role' => 'director_extension', 'name' => 'Carlos Javier'],
    // Alias kept for API / README examples
    ['email' => 'director@ride.local', 'role' => 'director_research', 'name' => 'Liza Mendoza'],

    // RIDE coordinators (by cluster)
    ['email' => 'coord.research.cluster1@ride.local', 'role' => 'rdo_coordinator', 'name' => 'Teresa Aquino', 'clusters' => ['Research Cluster I']],
    ['email' => 'coord.research.cluster2@ride.local', 'role' => 'rdo_coordinator', 'name' => 'Victor Santiago', 'clusters' => ['Research Cluster II']],
    ['email' => 'coord.extension.group1@ride.local', 'role' => 'field_extensionist', 'name' => 'Rafael Ilagan', 'clusters' => ['ESO Group 1']],
    ['email' => 'coord.extension.group2@ride.local', 'role' => 'field_extensionist', 'name' => 'Bianca Ferrer', 'clusters' => ['ESO Group 2']],

    // CET
    ['email' => 'coord.research.cet@ride.local', 'role' => 'coordinator_research', 'name' => 'Mark Santos', 'college' => 'CET'],
    ['email' => 'coord.extension.cet@ride.local', 'role' => 'coordinator_extension', 'name' => 'Grace Lim', 'college' => 'CET'],
    ['email' => 'dean.cet@ride.local', 'role' => 'dean', 'name' => 'Patricia Ong', 'college' => 'CET'],
    ['email' => 'faculty.cet@ride.local', 'role' => 'faculty', 'name' => 'John Cruz', 'college' => 'CET', 'program' => 'BS in Civil Engineering'],

    // CCAS
    ['email' => 'coord.research.cas@ride.local', 'role' => 'coordinator_research', 'name' => 'Elena Ramos', 'college' => 'CCAS'],
    ['email' => 'coord.extension.cas@ride.local', 'role' => 'coordinator_extension', 'name' => 'Paolo Garcia', 'college' => 'CCAS'],
    ['email' => 'dean.cas@ride.local', 'role' => 'dean', 'name' => 'Isabel Torres', 'college' => 'CCAS'],
    ['email' => 'faculty.cas@ride.local', 'role' => 'faculty', 'name' => 'Miguel Lopez', 'college' => 'CCAS', 'program' => 'BS in Social Work'],

    // CBEHM
    ['email' => 'coord.research.cbm@ride.local', 'role' => 'coordinator_research', 'name' => 'Daniel Tan', 'college' => 'CBEHM'],
    ['email' => 'coord.extension.cbm@ride.local', 'role' => 'coordinator_extension', 'name' => 'Monica Reyes', 'college' => 'CBEHM'],
    ['email' => 'dean.cbm@ride.local', 'role' => 'dean', 'name' => 'Antonio Flores', 'college' => 'CBEHM'],
    ['email' => 'faculty.cbm@ride.local', 'role' => 'faculty', 'name' => 'Rachel Gomez', 'college' => 'CBEHM', 'program' => 'BS in Business Administration'],
];
