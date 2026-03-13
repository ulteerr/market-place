import { expect, type APIRequestContext } from '@playwright/test';

const matchFirst = (html: string, pattern: RegExp): string | null => {
  const match = html.match(pattern);
  return match?.[1]?.trim() ?? null;
};

const decodeHtml = (value: string | null): string => {
  if (!value) {
    return '';
  }

  return value
    .replace(/&quot;/g, '"')
    .replace(/&#39;/g, "'")
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>')
    .replace(/&amp;/g, '&');
};

export const fetchServerHtml = async (
  request: APIRequestContext,
  path: string
): Promise<string> => {
  const response = await request.get(path);
  expect(response.ok()).toBeTruthy();
  return await response.text();
};

export const extractTitle = (html: string): string =>
  decodeHtml(matchFirst(html, /<title[^>]*>([\s\S]*?)<\/title>/i));

export const extractMetaDescription = (html: string): string =>
  decodeHtml(
    matchFirst(html, /<meta[^>]+name=["']description["'][^>]+content=["']([\s\S]*?)["'][^>]*>/i)
  );

export const extractH1Text = (html: string): string =>
  decodeHtml(matchFirst(html, /<h1[^>]*>([\s\S]*?)<\/h1>/i))
    .replace(/<[^>]+>/g, '')
    .trim();

export const extractJsonLdPayloads = (html: string): string[] => {
  return Array.from(
    html.matchAll(/<script[^>]+type=["']application\/ld\+json["'][^>]*>([\s\S]*?)<\/script>/gi)
  ).map((match) => match[1]?.trim() ?? '');
};
