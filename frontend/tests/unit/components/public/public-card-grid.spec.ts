// @vitest-environment jsdom
import { mount } from '@vue/test-utils';
import { defineComponent } from 'vue';
import { describe, expect, it } from 'vitest';
import PublicCardGrid from '~/components/public/PublicCardGrid/PublicCardGrid.vue';

const NuxtLinkStub = defineComponent({
  props: {
    to: { type: String, default: '' },
  },
  template: '<a :href="to"><slot /></a>',
});

describe('PublicCardGrid', () => {
  it('renders image-first activity cards with lazy images, badge and meta', () => {
    const wrapper = mount(PublicCardGrid, {
      props: {
        items: [
          {
            title: 'ФАЗ Зенит',
            description: 'Футбольная школа для детей.',
            to: '/sport/futbol/faz-zenit-uuid',
            imageUrl: 'https://example.com/cover.jpg',
            imageAlt: 'ФАЗ Зенит',
            eyebrow: 'Спорт / Футбол',
            price: 'Стоимость по запросу',
            meta: 'Чемпионы · Москва',
            badge: 'Рекомендуем',
            dataTest: 'activity-card',
          },
        ],
      },
      global: {
        stubs: {
          NuxtLink: NuxtLinkStub,
          UiCard: {
            template: '<article><slot /></article>',
          },
        },
      },
    });

    const image = wrapper.get('img');

    expect(wrapper.get('a').attributes('href')).toBe('/sport/futbol/faz-zenit-uuid');
    expect(image.attributes('src')).toBe('https://example.com/cover.jpg');
    expect(image.attributes('loading')).toBe('lazy');
    expect(image.attributes('alt')).toBe('ФАЗ Зенит');
    expect(wrapper.text()).toContain('Спорт / Футбол');
    expect(wrapper.text()).toContain('Стоимость по запросу');
    expect(wrapper.text()).toContain('Рекомендуем');
    expect(wrapper.text()).toContain('Чемпионы · Москва');
  });
});
