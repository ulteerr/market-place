export default {
  layout: {
    panelTitle: 'Admin Panel',
    shortPanelTitle: 'AP',
    closeSidebar: 'Закрыть меню',
    sidebarToggleExpand: 'Expand menu',
    sidebarToggleCollapse: 'Collapse menu',
    heading: 'Административная панель',
    toggleLightMode: 'Toggle light mode',
    toggleDarkMode: 'Toggle dark mode',
    menu: {
      dashboard: 'Главная',
      users: 'Пользователи',
      roles: 'Роли',
    },
    user: {
      guest: 'Гость',
      noEmail: 'Нет email',
    },
  },
  userMenu: {
    profile: 'Профиль',
    settings: 'Настройки',
    logout: 'Выйти',
  },
  entity: {
    shownCount: 'Показано {shown} из {total}.',
    desktopTable: 'Desktop: таблица',
    desktopCards: 'Desktop: карточки',
    modePlaceholder: 'Таблица',
    modes: {
      table: 'Таблица',
      tableCards: 'Таблица + карточки',
      cards: 'Карточки',
    },
  },
  toolbar: {
    search: 'Поиск',
    perPage: 'По {count}',
    find: 'Найти',
    reset: 'Сбросить',
  },
  pagination: {
    summary: 'Страница {current} / {last}. На странице: {perPage}.',
    back: 'Назад',
    forward: 'Вперед',
  },
  actions: {
    show: 'Показать',
    edit: 'Редактировать',
    delete: 'Удалить',
  },
  dashboard: {
    title: 'Панель управления',
    subtitle: 'Выберите модуль в левом меню. Здесь отображается контент выбранного раздела.',
    usersTitle: 'Пользователи',
    usersSubtitle: 'Управление ролями, доступами и профилями администраторов.',
    contentTitle: 'Контент',
    contentSubtitle: 'Редактирование разделов, настройка видимости и модерирование.',
  },
  profile: {
    title: 'Мой профиль',
    subtitle: 'Редактирование личных данных текущего пользователя.',
    fields: {
      firstName: 'Имя',
      lastName: 'Фамилия',
      middleName: 'Отчество',
      email: 'Email',
    },
    saving: 'Сохраняем...',
    errors: {
      update: 'Не удалось обновить профиль.',
    },
  },
  settings: {
    title: 'Настройки пользователя',
    subtitle: 'Настройки автоматически сохраняются в браузере и синхронизируются с backend.',
    theme: {
      label: 'Тёмная тема',
      description: 'Переключает цветовую схему интерфейса',
      hint: 'Сохраняется в профиле пользователя',
    },
    menu: {
      label: 'Collapse menu',
      description: 'Сворачивает боковое меню до режима с иконками',
      hint: 'Управляет шириной sidebar в админке',
    },
  },
  users: {
    index: {
      title: 'Пользователи',
      subtitle: 'Поиск, сортировка, limit и серверная пагинация.',
      createLabel: 'Новый пользователь',
      searchPlaceholder: 'Поиск: фамилия, имя, отчество, email, телефон, роль',
      empty: 'Пользователи не найдены.',
      headers: {
        lastName: 'Фамилия',
        firstName: 'Имя',
        middleName: 'Отчество',
        access: 'Доступ',
        actions: 'Действия',
      },
      card: {
        lastName: 'Фамилия: {value}',
        firstName: 'Имя: {value}',
        middleName: 'Отчество: {value}',
      },
      sort: {
        lastName: 'Фамилия',
        firstName: 'Имя',
        middleName: 'Отчество',
        access: 'Доступ',
      },
      access: {
        unknown: 'Неизвестно',
        admin: 'Админ-панель',
        basic: 'Без админ-доступа',
      },
    },
    new: {
      title: 'Новый пользователь',
      subtitle: 'Создание пользователя в `/api/admin/users`.',
      fields: {
        firstName: 'Имя',
        lastName: 'Фамилия',
        middleName: 'Отчество',
        email: 'Email',
        phone: 'Телефон',
        password: 'Пароль',
        passwordConfirmation: 'Подтверждение пароля',
        roles: 'Роли',
      },
      rolesPlaceholder: 'Выберите роли',
      saving: 'Сохраняем...',
      errors: {
        create: 'Не удалось создать пользователя.',
      },
    },
    show: {
      title: 'Профиль пользователя',
      subtitle: 'Show-страница пользователя.',
      labels: {
        firstName: 'Имя',
        lastName: 'Фамилия',
        middleName: 'Отчество',
        email: 'Email',
        phone: 'Телефон',
        roles: 'Роли',
      },
      errors: {
        invalidId: 'Некорректный идентификатор пользователя.',
        load: 'Не удалось загрузить пользователя.',
      },
    },
    edit: {
      title: 'Редактирование пользователя',
      subtitle: 'Edit-страница пользователя.',
      fields: {
        firstName: 'Имя',
        lastName: 'Фамилия',
        middleName: 'Отчество',
        email: 'Email',
        phone: 'Телефон',
        newPassword: 'Новый пароль',
        newPasswordConfirmation: 'Подтверждение нового пароля',
        roles: 'Роли',
      },
      rolesPlaceholder: 'Выберите роли',
      saving: 'Сохраняем...',
      errors: {
        invalidId: 'Некорректный идентификатор пользователя.',
        load: 'Не удалось загрузить пользователя.',
        update: 'Не удалось обновить пользователя.',
      },
    },
    confirmDelete: 'Удалить пользователя {name}?',
  },
  roles: {
    index: {
      title: 'Роли',
      subtitle: 'Поиск, сортировка, limit и серверная пагинация.',
      createLabel: 'Новая роль',
      searchPlaceholder: 'Поиск: code или label',
      empty: 'Роли не найдены.',
      headers: {
        code: 'Code',
        label: 'Label',
        type: 'Тип',
        actions: 'Действия',
      },
      sort: {
        code: 'Code',
        label: 'Label',
        type: 'Тип',
      },
      type: {
        system: 'Системная',
        custom: 'Пользовательская',
      },
      cardLabelFallback: 'Без label',
    },
    new: {
      title: 'Новая роль',
      subtitle: 'Создание роли в `/api/admin/roles`.',
      fields: {
        code: 'Code',
        label: 'Label',
      },
      saving: 'Сохраняем...',
      errors: {
        create: 'Не удалось создать роль.',
      },
    },
    show: {
      title: 'Роль',
      subtitle: 'Show-страница роли.',
      labels: {
        code: 'Code',
        label: 'Label',
        type: 'Тип',
      },
      errors: {
        invalidId: 'Некорректный идентификатор роли.',
        load: 'Не удалось загрузить роль.',
      },
    },
    edit: {
      title: 'Редактирование роли',
      subtitle: 'Edit-страница роли.',
      fields: {
        code: 'Code',
        label: 'Label',
      },
      systemLocked: 'Системные роли редактировать нельзя.',
      saving: 'Сохраняем...',
      errors: {
        invalidId: 'Некорректный идентификатор роли.',
        load: 'Не удалось загрузить роль.',
        update: 'Не удалось обновить роль.',
      },
    },
    errors: {
      loadList: 'Не удалось загрузить роли.',
      delete: 'Не удалось удалить роль.',
    },
    confirmDelete: 'Удалить роль {code}?',
  },
  errors: {
    users: {
      loadList: 'Не удалось загрузить пользователей.',
      delete: 'Не удалось удалить пользователя.',
    },
  },
} as const;
