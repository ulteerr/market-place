# Categories + Activities Final QA Matrix

Матрица ниже покрывает завершенные deliverable-блоки `CAT/ACT`.
Meta-маркеры `START`, `SPR` и `DOD` сюда не включены: это контрольные чекпойнты, а не отдельные поставляемые артефакты.

| Задачи | Код есть | Backend test | Playwright | Responsive Playwright | Риск | Примечание |
| --- | --- | --- | --- | --- | --- | --- |
| `CAT-A01..CAT-A07` | да | да | нет | нет | medium | Контракт дерева и миграции покрыты поздними backend-интеграционными тестами. |
| `ACT-A01..ACT-A09` | да | да | нет | нет | medium | Контракт `activities/leads` закреплен кодом и backend feature-тестами. |
| `CAT-B01..CAT-B14` | да | да | нет | нет | low | Backend-модуль `Categories` полностью покрыт feature/unit тестами. |
| `ACT-B01..ACT-B17` | да | да | нет | нет | low | Backend-модуль `Activities + Leads` покрыт feature/unit/media/schedules тестами. |
| `CAT-C01..CAT-C09` | да | да | нет | нет | low | Интеграционный контракт `categories <-> activities` подтвержден runtime и backend tests. |
| `ACT-C10..ACT-C12` | да | да | нет | нет | low | Lead-flow сценарий подтвержден backend feature-тестами. |
| `CAT-D101..CAT-D212` | да | да | да | да | low | Admin CRUD, public category navigation и responsive UI покрыты e2e. |
| `ACT-D101..ACT-D213` | да | да | да | да | low | Admin CRUD, lead inbox screens и responsive behavior покрыты e2e. |
| `ACT-E01..ACT-E09` | да | да | да | да | low | Public feed, detail page, canonical routing и public lead form покрыты unit/e2e. |
| `CAT-E01..CAT-E08` | да | да | да | да | low | Public category navigation/pages покрыты unit/e2e/responsive. |
| `CAT-F01..CAT-F06` | да | нет | нет | нет | low | Swagger + docs проверены как deliverable, а не runtime. |
| `ACT-F01..ACT-F10` | да | нет | нет | нет | low | Swagger + docs проверены как deliverable, а не runtime. |
| `CAT-G101..CAT-G105` | да | да | нет | нет | low | Backend test block для `Categories`. |
| `CAT-G201..CAT-G205` | да | нет | да | да | low | Frontend/unit/e2e/responsive block для `Categories`. |
| `ACT-G101..ACT-G107` | да | да | нет | нет | low | Backend test block для `Activities + Leads`. |
| `ACT-G201..ACT-G206` | да | нет | да | да | low | Frontend/unit/e2e/responsive block для `Activities + Leads`. |

## Evidence

- Backend `Categories`: feature/unit/integration tests in [backend/app/Modules/Categories/Tests](/var/www/project-root/backend/app/Modules/Categories/Tests)
- Backend `Activities`: feature/unit tests in [backend/app/Modules/Activities/Tests](/var/www/project-root/backend/app/Modules/Activities/Tests)
- Frontend `Categories` e2e in [frontend/tests/e2e/admin/categories](/var/www/project-root/frontend/tests/e2e/admin/categories) and [frontend/tests/e2e/public/categories.spec.ts](/var/www/project-root/frontend/tests/e2e/public/categories.spec.ts)
- Frontend `Activities` e2e in [frontend/tests/e2e/admin/activities](/var/www/project-root/frontend/tests/e2e/admin/activities), [frontend/tests/e2e/public/activities.spec.ts](/var/www/project-root/frontend/tests/e2e/public/activities.spec.ts), [frontend/tests/e2e/public/activities-responsive.spec.ts](/var/www/project-root/frontend/tests/e2e/public/activities-responsive.spec.ts)
- Lead inbox e2e in [frontend/tests/e2e/admin/activity-leads](/var/www/project-root/frontend/tests/e2e/admin/activity-leads) and [frontend/tests/e2e/private/activity-leads.spec.ts](/var/www/project-root/frontend/tests/e2e/private/activity-leads.spec.ts)
