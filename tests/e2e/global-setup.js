import { execSync } from 'child_process'
import path from 'path'

/**
 * Runs once before the entire Playwright test suite.
 * Re-seeds the database so tests always start from a known state.
 */
export default async function globalSetup() {
  const root = path.resolve(process.cwd())
  console.log('\n[setup] Refreshing & seeding the database...')
  try {
    execSync('php artisan migrate:fresh --seed --force', {
      cwd: root,
      stdio: 'inherit',
      timeout: 120_000,
    })
    console.log('[setup] Database ready.\n')
  } catch (err) {
    console.error('[setup] Database seed failed — tests may use stale data.', err.message)
  }
}
