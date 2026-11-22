import { APIRequestContext, Page, expect, test } from '@playwright/test';

const BASE_URL = process.env['E2E_BASE_URL'] ?? 'http://localhost:8080';

const COMPTES = {
  admin: { email: 'admin@accommity.ma', password: 'Admin1234!' },
  hote: { email: 'hote.demo@accommity.ma', password: 'Password123!' },
  voyageur: { email: 'voyageur.demo@accommity.ma', password: 'Password123!' },
};

async function tokenPour(
  request: APIRequestContext,
  compte: { email: string; password: string },
): Promise<string> {
  const response = await request.post(`${BASE_URL}/api/auth/login`, {
    data: compte,
  });

  expect(response.ok(), `Connexion API échouée pour ${compte.email}`).toBeTruthy();

  const payload = await response.json();
  expect(payload.data?.token).toBeTruthy();

  return payload.data.token as string;
}

async function ouvrirAvecToken(page: Page, token: string, chemin: string): Promise<void> {
  await page.addInitScript((value: string) => {
    window.localStorage.setItem('accommity_auth_token', value);
  }, token);
  await page.goto(chemin);
}

test.describe('Smoke E2E Accommity', () => {
  test('l’accueil public et le catalogue sont accessibles', async ({ page }) => {
    await page.goto('/');

    await expect(
      page.getByRole('heading', {
        level: 1,
        name: /Réservez des riads historiques/i,
      }),
    ).toBeVisible();

    await expect(page.getByRole('heading', { name: /Hébergements à la une/i })).toBeVisible();
    await expect(page.getByRole('link', { name: /Explorer les hébergements/i })).toBeVisible();
  });

  test('un hôte se connecte et accède à ses annonces', async ({ page }) => {
    await page.goto('/auth/login');

    await page.locator('#login-email').fill(COMPTES.hote.email);
    await page.locator('#login-password').fill(COMPTES.hote.password);
    await page.getByRole('button', { name: /Se connecter/i }).click();

    await expect(page).not.toHaveURL(/\/auth\/login/);
    await expect(page.locator('header').getByRole('link', { name: 'Mes annonces' })).toBeVisible();

    await page.getByRole('link', { name: 'Mes annonces' }).click();
    await expect(page.getByRole('heading', { level: 1, name: 'Mes annonces' })).toBeVisible();
    await expect(page.getByRole('link', { name: /Créer une annonce/i })).toBeVisible();
  });

  test('la fiche d’une annonce expose le bloc d’avis', async ({ page, request }) => {
    const response = await request.get(`${BASE_URL}/api/annonces`);
    expect(response.ok()).toBeTruthy();

    const payload = await response.json();
    const annonce = payload.data?.items?.[0];

    expect(annonce?.id_annonce, 'Aucune annonce publiée : le seeder de démo doit tourner').toBeTruthy();

    await page.goto(`/annonces/${annonce.id_annonce}`);

    await expect(
      page.getByRole('heading', { name: /Avis des voyageurs/i }),
    ).toBeVisible();
  });

  test('un administrateur accède au back-office', async ({ page, request }) => {
    const token = await tokenPour(request, COMPTES.admin);

    await ouvrirAvecToken(page, token, '/admin');

    await expect(
      page.getByRole('heading', { level: 1, name: /Tableau de bord Administrateur/i }),
    ).toBeVisible();
  });

  test('un voyageur retrouve ses évaluations', async ({ page, request }) => {
    const token = await tokenPour(request, COMPTES.voyageur);

    await ouvrirAvecToken(page, token, '/profil/evaluations');

    await expect(page.getByRole('heading', { level: 1, name: 'Mes évaluations' })).toBeVisible();
    await expect(page.getByRole('tab', { name: /Mes avis publiés/i })).toBeVisible();
  });

  test('la déconnexion ramène l’utilisateur à l’état invité', async ({ page }) => {
    await page.goto('/auth/login');
    await page.locator('#login-email').fill(COMPTES.hote.email);
    await page.locator('#login-password').fill(COMPTES.hote.password);
    await page.getByRole('button', { name: /Se connecter/i }).click();

    await expect(page.locator('header').getByTitle('Déconnexion')).toBeVisible();
    await page.locator('header').getByTitle('Déconnexion').click();

    await expect(page.locator('header').getByRole('link', { name: 'Connexion' })).toBeVisible();
    await expect(page.locator('header').getByTitle('Déconnexion')).toHaveCount(0);
  });
});
