export default {
  save: 'Сохранить',
  cancel: 'Отмена',
  create: 'Создать',
  edit: 'Редактировать',
  backToList: 'К списку',
  loading: 'Загрузка...',
  dash: '—',
  lastSeen: {
    justNow: 'только что',
    ago: '{value} {unit} назад',
    units: {
      minute: {
        one: 'минута',
        few: 'минуты',
        many: 'минут',
      },
      hour: {
        one: 'час',
        few: 'часа',
        many: 'часов',
      },
      day: {
        one: 'день',
        few: 'дня',
        many: 'дней',
      },
      month: {
        one: 'месяц',
        few: 'месяца',
        many: 'месяцев',
      },
      year: {
        one: 'год',
        few: 'года',
        many: 'лет',
      },
    },
  },
} as const;
