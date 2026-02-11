export default {
  layout: {
    panelTitle: 'Admin Panel',
    shortPanelTitle: 'AP',
    closeSidebar: 'Close menu',
    sidebarToggleExpand: 'Expand menu',
    sidebarToggleCollapse: 'Collapse menu',
    heading: 'Administration panel',
    toggleLightMode: 'Toggle light mode',
    toggleDarkMode: 'Toggle dark mode',
    menu: {
      dashboard: 'Dashboard',
      users: 'Users',
      roles: 'Roles',
    },
    user: {
      guest: 'Guest',
      noEmail: 'No email',
    },
  },
  userMenu: {
    profile: 'Profile',
    settings: 'Settings',
    logout: 'Logout',
  },
  entity: {
    shownCount: 'Showing {shown} of {total}.',
    desktopTable: 'Desktop: table',
    desktopCards: 'Desktop: cards',
    modePlaceholder: 'Table',
    modes: {
      table: 'Table',
      tableCards: 'Table + cards',
      cards: 'Cards',
    },
  },
  toolbar: {
    search: 'Search',
    perPage: '{count} per page',
    find: 'Find',
    reset: 'Reset',
  },
  pagination: {
    summary: 'Page {current} / {last}. Per page: {perPage}.',
    back: 'Back',
    forward: 'Next',
  },
  actions: {
    show: 'Show',
    edit: 'Edit',
    delete: 'Delete',
  },
  dashboard: {
    title: 'Dashboard',
    subtitle: 'Select a module in the left menu. The selected section content is shown here.',
    usersTitle: 'Users',
    usersSubtitle: 'Manage roles, accesses, and admin profiles.',
    contentTitle: 'Content',
    contentSubtitle: 'Edit sections, configure visibility, and moderate.',
  },
  profile: {
    title: 'My profile',
    subtitle: 'Edit personal data of the current user.',
    fields: {
      firstName: 'First name',
      lastName: 'Last name',
      middleName: 'Middle name',
      email: 'Email',
    },
    saving: 'Saving...',
    errors: {
      update: 'Failed to update profile.',
    },
  },
  settings: {
    title: 'User settings',
    subtitle: 'Settings are saved automatically in browser and synced with backend.',
    theme: {
      label: 'Dark theme',
      description: 'Switches interface color scheme',
      hint: 'Stored in user profile',
    },
    menu: {
      label: 'Collapse menu',
      description: 'Collapses sidebar to icons-only mode',
      hint: 'Controls admin sidebar width',
    },
  },
  users: {
    index: {
      title: 'Users',
      subtitle: 'Search, sorting, limit and server pagination.',
      createLabel: 'New user',
      searchPlaceholder: 'Search: last name, first name, middle name, email, phone, role',
      empty: 'No users found.',
      headers: {
        lastName: 'Last name',
        firstName: 'First name',
        middleName: 'Middle name',
        access: 'Access',
        actions: 'Actions',
      },
      card: {
        lastName: 'Last name: {value}',
        firstName: 'First name: {value}',
        middleName: 'Middle name: {value}',
      },
      sort: {
        lastName: 'Last name',
        firstName: 'First name',
        middleName: 'Middle name',
        access: 'Access',
      },
      access: {
        unknown: 'Unknown',
        admin: 'Admin panel',
        basic: 'No admin access',
      },
    },
    new: {
      title: 'New user',
      subtitle: 'Create user in `/api/admin/users`.',
      fields: {
        firstName: 'First name',
        lastName: 'Last name',
        middleName: 'Middle name',
        email: 'Email',
        phone: 'Phone',
        password: 'Password',
        passwordConfirmation: 'Password confirmation',
        roles: 'Roles',
      },
      rolesPlaceholder: 'Select roles',
      saving: 'Saving...',
      errors: {
        create: 'Failed to create user.',
      },
    },
    show: {
      title: 'User profile',
      subtitle: 'User show page.',
      labels: {
        firstName: 'First name',
        lastName: 'Last name',
        middleName: 'Middle name',
        email: 'Email',
        phone: 'Phone',
        roles: 'Roles',
      },
      errors: {
        invalidId: 'Invalid user identifier.',
        load: 'Failed to load user.',
      },
    },
    edit: {
      title: 'Edit user',
      subtitle: 'User edit page.',
      fields: {
        firstName: 'First name',
        lastName: 'Last name',
        middleName: 'Middle name',
        email: 'Email',
        phone: 'Phone',
        newPassword: 'New password',
        newPasswordConfirmation: 'New password confirmation',
        roles: 'Roles',
      },
      rolesPlaceholder: 'Select roles',
      saving: 'Saving...',
      errors: {
        invalidId: 'Invalid user identifier.',
        load: 'Failed to load user.',
        update: 'Failed to update user.',
      },
    },
    confirmDelete: 'Delete user {name}?',
  },
  roles: {
    index: {
      title: 'Roles',
      subtitle: 'Search, sorting, limit and server pagination.',
      createLabel: 'New role',
      searchPlaceholder: 'Search: code or label',
      empty: 'No roles found.',
      headers: {
        code: 'Code',
        label: 'Label',
        type: 'Type',
        actions: 'Actions',
      },
      sort: {
        code: 'Code',
        label: 'Label',
        type: 'Type',
      },
      type: {
        system: 'System',
        custom: 'Custom',
      },
      cardLabelFallback: 'No label',
    },
    new: {
      title: 'New role',
      subtitle: 'Create role in `/api/admin/roles`.',
      fields: {
        code: 'Code',
        label: 'Label',
      },
      saving: 'Saving...',
      errors: {
        create: 'Failed to create role.',
      },
    },
    show: {
      title: 'Role',
      subtitle: 'Role show page.',
      labels: {
        code: 'Code',
        label: 'Label',
        type: 'Type',
      },
      errors: {
        invalidId: 'Invalid role identifier.',
        load: 'Failed to load role.',
      },
    },
    edit: {
      title: 'Edit role',
      subtitle: 'Role edit page.',
      fields: {
        code: 'Code',
        label: 'Label',
      },
      systemLocked: 'System roles cannot be edited.',
      saving: 'Saving...',
      errors: {
        invalidId: 'Invalid role identifier.',
        load: 'Failed to load role.',
        update: 'Failed to update role.',
      },
    },
    errors: {
      loadList: 'Failed to load roles.',
      delete: 'Failed to delete role.',
    },
    confirmDelete: 'Delete role {code}?',
  },
  errors: {
    users: {
      loadList: 'Failed to load users.',
      delete: 'Failed to delete user.',
    },
  },
} as const;
