# Public City Selection

## Status
Выбор города в public части marketplace реализован и проверен. Документ фиксирует актуальный контракт, поведение, покрытие тестами и будущие улучшения вне текущего scope.

## Product Contract
- В public UI используем термин `город`.
- Технический canonical filter для витрины: `city_id`.
- `geo region` как отдельная сущность в этой итерации не участвует.
- Пользовательская настройка хранится в `user.settings.public_city`.
- Для гостя используется существующий guest settings fallback.
- После логина guest settings мержатся в account settings.
- Ручной выбор города имеет приоритет над IP-автоопределением.
- Закрытие модалки автоопределения не очищает город.
- Если IP-город не удалось сопоставить с нашим справочником, временно выбираем первый город по `id`.
- `favorites` не фильтруются автоматически по выбранному городу.

## `public_city` Shape
```ts
type PublicCitySetting = {
  city_id: string;
  city_name: string;
  source: 'ip_auto' | 'manual';
  region_id: string | null;
  region_name: string | null;
  country_id: string;
  country_name: string;
};
```

## Runtime Behavior
- Первый заход без ручного города запускает IP-определение.
- Автоопределенный город применяется без обязательного подтверждения.
- Пользователь видит модалку с авто-выбранным городом при IP match или fallback default.
- Закрытие модалки оставляет автоопределенный город активным.
- `Нет, выбрать другой` открывает ручной выбор города.
- Ручной выбор сохраняет `source = manual`.
- Повторное IP-определение не перетирает ручной выбор.
- Выбранный город виден в public header.
- Выбор переживает reload.
- Guest выбор хранится в guest preferences.
- После логина guest выбор мержится в account settings.
- Public home/catalog/category/search/suggest используют выбранный `city_id`.
- Favorites остаются личным списком пользователя без city filter.

## Implementation Summary
- Settings contract: добавлен `public_city`, нормализация, runtime merge, guest fallback и account merge после логина.
- Backend geo: добавлены public endpoints для списка городов и определения города по IP.
- Backend fallback: если IP lookup не дал валидный город, используется первый город по `id`.
- Header UI: блок города оживлен, открывает выбор города, показывает модалку автоопределения и поддерживает сценарий `Нет, выбрать другой`.
- Persistence: выбор переживает reload у гостя и авторизованного пользователя.
- Content filtering: `city_id` централизованно подмешивается в home, catalog, category pages, search results и search suggest.
- SSR/cache: добавлен базовый cache hardening для public части, чтобы не смешивать пользовательские настройки между городами.

## Verification Log
- `docker compose exec frontend npx playwright test tests/e2e/public/city-selection.spec.ts`: 27 passed.
- `docker compose exec frontend npx playwright test tests/e2e/layout/public-header-responsive.spec.ts`: 9 passed.
- `docker compose exec frontend npm run test:unit`: 47 files / 123 tests passed.
- `docker compose exec backend php artisan test app/Modules/Geo/Tests/Feature/PublicCitySelectionTest.php app/Modules/Activities/Tests/Feature/ActivitiesPublicReadTest.php app/Modules/Search/Tests/Feature/SearchPublicApiTest.php app/Modules/Users/Tests/Feature/UpdateMeSettingsTest.php`: 24 passed.
- `docker compose exec frontend npx playwright test tests/e2e/admin/auth.spec.ts`: 15 passed.

## Future Improvements
- Заменить fallback "первый город по `id`" на site settings default city.
- Если появится настоящая географическая иерархия, добавить отдельный уровень `region`, не переопределяя смысл `public_city`.
- Уточнить production GeoIP provider, proxy headers и trusted proxy настройки.
- Если справочник городов станет большим, сохранить selector search-driven и не переходить к preload всех городов.
