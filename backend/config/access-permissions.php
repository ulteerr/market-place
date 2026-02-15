<?php

declare(strict_types=1);

use Modules\Users\Enums\RoleCode;

return [
    "permissions" => [
        "admin" => [
            "admin.panel.access" => "Доступ в админ-панель",
            "admin.users.read" => "Просмотр пользователей в админке",
            "admin.users.create" => "Создание пользователей в админке",
            "admin.users.update" => "Изменение пользователей в админке",
            "admin.users.delete" => "Удаление пользователей в админке",
            "admin.roles.read" => "Просмотр ролей в админке",
            "admin.roles.create" => "Создание ролей в админке",
            "admin.roles.update" => "Изменение ролей в админке",
            "admin.roles.delete" => "Удаление ролей в админке",
            "admin.changelog.read" => "Просмотр changelog в админке",
            "admin.changelog.rollback" => "Запрос rollback changelog в админке",
            "admin.action-log.read" => "Просмотр action log в админке",
            "admin.action-log.update" => "Изменение action log в админке",
            "admin.action-log.delete" => "Удаление action log в админке",
        ],
        "org" => [
            "org.company.profile.read" => "Просмотр личного кабинета компании",
            "org.company.profile.update" => "Редактирование личного кабинета компании",
            "org.company.profile.delete" => "Удаление личного кабинета компании",
            "org.members.read" => "Просмотр участников организации",
            "org.members.write" => "Управление участниками организации",
            "org.children.read" => "Просмотр детей в организации",
            "org.children.write" => "Управление детьми в организации",
        ],
        "user" => [
            "user.profile.read" => "Просмотр личного кабинета пользователя",
            "user.profile.update" => "Редактирование личного кабинета пользователя",
            "user.profile.delete" => "Удаление личного кабинета пользователя",
        ],
    ],

    "roles" => [
        RoleCode::SUPER_ADMIN->value => [],
        RoleCode::ADMIN->value => [
            "admin.panel.access",
            "admin.users.read",
            "admin.users.create",
            "admin.users.update",
            "admin.users.delete",
            "admin.roles.read",
            "admin.roles.create",
            "admin.roles.update",
            "admin.roles.delete",
            "admin.changelog.read",
            "admin.changelog.rollback",
            "admin.action-log.read",
            "org.company.profile.read",
            "org.company.profile.update",
            "org.company.profile.delete",
            "org.members.read",
            "org.members.write",
            "org.children.read",
            "org.children.write",
            "user.profile.read",
            "user.profile.update",
            "user.profile.delete",
        ],
        RoleCode::MODERATOR->value => [
            "admin.panel.access",
            "admin.users.read",
            "admin.users.update",
            "admin.roles.read",
            "admin.changelog.read",
            "org.members.read",
            "org.children.read",
            "user.profile.read",
            "user.profile.update",
        ],
        RoleCode::PARTICIPANT->value => ["user.profile.read", "user.profile.update"],
    ],
];
