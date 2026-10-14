<?php

declare(strict_types=1);

/**
 * Demo accounts for development installs.
 * Password for all: password123
 *
 * Seed / refresh with:
 *   c:\xampp\php\php.exe scripts\seed-demo-accounts.php
 */
return [
    // University-wide
    ['email' => 'admin@ride.local', 'password' => 'password123', 'role' => 'RIDE Admin (system)', 'name' => 'Ana Reyes', 'college' => 'University-wide'],
    ['email' => 'vpride@ride.local', 'password' => 'password123', 'role' => 'Admin / VPRIDE', 'name' => 'Ramon Villanueva', 'college' => 'University-wide'],
    ['email' => 'director.research@ride.local', 'password' => 'password123', 'role' => 'RDO Director', 'name' => 'Liza Mendoza', 'college' => 'University-wide'],
    ['email' => 'director.extension@ride.local', 'password' => 'password123', 'role' => 'ESO Director', 'name' => 'Carlos Javier', 'college' => 'University-wide'],
    // Alias kept for API / README examples
    ['email' => 'director@ride.local', 'password' => 'password123', 'role' => 'RDO Director', 'name' => 'Liza Mendoza', 'college' => 'University-wide'],

    // CET
    ['email' => 'coord.research.cet@ride.local', 'password' => 'password123', 'role' => 'Research Coordinator', 'name' => 'Mark Santos', 'college' => 'CET'],
    ['email' => 'coord.extension.cet@ride.local', 'password' => 'password123', 'role' => 'Field Extensionist', 'name' => 'Grace Lim', 'college' => 'CET'],
    ['email' => 'dean.cet@ride.local', 'password' => 'password123', 'role' => 'College Dean', 'name' => 'Patricia Ong', 'college' => 'CET'],
    ['email' => 'faculty.research.cet@ride.local', 'password' => 'password123', 'role' => 'Faculty (Research)', 'name' => 'John Cruz', 'college' => 'CET', 'program' => 'BS in Civil Engineering'],
    ['email' => 'faculty.extension.cet@ride.local', 'password' => 'password123', 'role' => 'Faculty (Extension)', 'name' => 'Nina Bautista', 'college' => 'CET', 'program' => 'BS in Electrical Engineering'],
    ['email' => 'extensionworker.cet@ride.local', 'password' => 'password123', 'role' => 'Extension Worker', 'name' => 'Rafael Ilagan', 'college' => 'CET', 'program' => 'BS in Electrical Engineering'],

    // CAS
    ['email' => 'coord.research.cas@ride.local', 'password' => 'password123', 'role' => 'Research Coordinator', 'name' => 'Elena Ramos', 'college' => 'CAS'],
    ['email' => 'coord.extension.cas@ride.local', 'password' => 'password123', 'role' => 'Field Extensionist', 'name' => 'Paolo Garcia', 'college' => 'CAS'],
    ['email' => 'dean.cas@ride.local', 'password' => 'password123', 'role' => 'College Dean', 'name' => 'Isabel Torres', 'college' => 'CAS'],
    ['email' => 'faculty.research.cas@ride.local', 'password' => 'password123', 'role' => 'Faculty (Research)', 'name' => 'Miguel Lopez', 'college' => 'CAS', 'program' => 'BS in Social Work'],
    ['email' => 'faculty.extension.cas@ride.local', 'password' => 'password123', 'role' => 'Faculty (Extension)', 'name' => 'Sara Dela Cruz', 'college' => 'CAS', 'program' => 'Bachelor of Secondary Education'],
    ['email' => 'extensionworker.cas@ride.local', 'password' => 'password123', 'role' => 'Extension Worker', 'name' => 'Bianca Ferrer', 'college' => 'CAS', 'program' => 'Bachelor of Secondary Education'],

    // CBM
    ['email' => 'coord.research.cbm@ride.local', 'password' => 'password123', 'role' => 'Research Coordinator', 'name' => 'Daniel Tan', 'college' => 'CBM'],
    ['email' => 'coord.extension.cbm@ride.local', 'password' => 'password123', 'role' => 'Field Extensionist', 'name' => 'Monica Reyes', 'college' => 'CBM'],
    ['email' => 'dean.cbm@ride.local', 'password' => 'password123', 'role' => 'College Dean', 'name' => 'Antonio Flores', 'college' => 'CBM'],
    ['email' => 'faculty.research.cbm@ride.local', 'password' => 'password123', 'role' => 'Faculty (Research)', 'name' => 'Rachel Gomez', 'college' => 'CBM', 'program' => 'BS in Business Administration'],
    ['email' => 'faculty.extension.cbm@ride.local', 'password' => 'password123', 'role' => 'Faculty (Extension)', 'name' => 'Kevin Navarro', 'college' => 'CBM', 'program' => 'BS in Hospitality Management'],
    ['email' => 'extensionworker.cbm@ride.local', 'password' => 'password123', 'role' => 'Extension Worker', 'name' => 'Diego Ocampo', 'college' => 'CBM', 'program' => 'BS in Hospitality Management'],
];
