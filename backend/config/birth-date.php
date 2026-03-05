<?php

declare(strict_types=1);

return [
    "users" => [
        "disallow_future" => (bool) env("BIRTH_DATE_USERS_DISALLOW_FUTURE", true),
        "min_age_years" => env("BIRTH_DATE_USERS_MIN_AGE", 14),
    ],
    "children" => [
        "disallow_future" => (bool) env("BIRTH_DATE_CHILDREN_DISALLOW_FUTURE", true),
        "min_parent_age_gap_years" => env("BIRTH_DATE_CHILDREN_MIN_PARENT_GAP", 12),
        "require_parent_birth_date" => (bool) env(
            "BIRTH_DATE_CHILDREN_REQUIRE_PARENT_BIRTH_DATE",
            false,
        ),
    ],
];
