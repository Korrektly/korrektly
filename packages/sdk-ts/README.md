<div align="center">

# @korrektly/sdk
A lightweight TypeScript/JavaScript SDK for tracking application installations and usage with the Korrektly platform.
</div>

<a id="installation"></a>
## Installation

```bash
# Using npm
npm install @korrektly/sdk

# Using yarn
yarn add @korrektly/sdk

# Using pnpm
pnpm add @korrektly/sdk

# Using bun
bun add @korrektly/sdk
```

<a id="quick-start"></a>
## Quick Start

```typescript
import { KorrektlyClient } from '@korrektly/sdk';

// Initialize the client
const korrektly = new KorrektlyClient({
  appId: 'your-app-id',
  instanceId: 'unique-instance-identifier',
  appVersion: '1.0.0', // optional
  debug: false, // optional, defaults to false
  disabled: false // optional, defaults to false
});

// That's it! Installation tracking happens automatically
```

<a id="configuration-options"></a>
## Configuration Options

### KorrektlyClientOptions

| Option | Type | Required | Default | Description |
|--------|------|----------|---------|-------------|
| `appId` | `string` | ✅ | - | The unique ID of your application |
| `instanceId` | `string` | ✅ | - | A unique identifier for this instance/installation |
| `appVersion` | `string` | ❌ | - | The version of your application |
| `disabled` | `boolean` | ❌ | `false` | Completely disable Korrektly tracking |
| `debug` | `boolean` | ❌ | `false` | Enable debug logging to console |

<a id="usage-examples"></a>
## Usage Examples

<a id="basic-usage"></a>
### Basic Usage

```typescript
import { KorrektlyClient } from '@korrektly/sdk';

const korrektly = new KorrektlyClient({
  appId: 'my-awesome-app',
  instanceId: '12345-67890-abcdef'
});
```

<a id="with-version-tracking"></a>
### With Version Tracking

```typescript
import { KorrektlyClient } from '@korrektly/sdk';

const korrektly = new KorrektlyClient({
  appId: 'my-awesome-app',
  instanceId: '12345-67890-abcdef',
  appVersion: '2.1.0'
});
```

<a id="development-environment"></a>
### Development Environment

```typescript
import { KorrektlyClient } from '@korrektly/sdk';

const korrektly = new KorrektlyClient({
  appId: 'my-awesome-app',
  instanceId: '12345-67890-abcdef',
  appVersion: '2.1.0-dev',
  debug: true, // Enable logging
  disabled: process.env.NODE_ENV === 'development' // Disable in dev
});
```

<a id="electron-app-example"></a>
### Electron App Example

```typescript
import { KorrektlyClient } from '@korrektly/sdk';
import { machineId } from 'node-machine-id';

// Use machine ID as instance identifier for desktop apps
const instanceId = await machineId();

const korrektly = new KorrektlyClient({
  appId: 'my-electron-app',
  instanceId,
  appVersion: app.getVersion()
});
```

<a id="browser-extension-example"></a>
### Browser Extension Example

```typescript
import { KorrektlyClient } from '@korrektly/sdk';

// Generate or retrieve a unique installation ID
const getOrCreateInstanceId = () => {
  let instanceId = localStorage.getItem('korrektly-instance-id');
  if (!instanceId) {
    instanceId = crypto.randomUUID();
    localStorage.setItem('korrektly-instance-id', instanceId);
  }
  return instanceId;
};

const korrektly = new KorrektlyClient({
  appId: 'my-browser-extension',
  instanceId: getOrCreateInstanceId(),
  appVersion: chrome.runtime.getManifest().version
});
```

<a id="persistent-instance-id-storage"></a>
## Persistent Instance ID Storage

Since the `instanceId` needs to remain consistent across app restarts, here are examples for different storage solutions:

<a id="file-based-storage-nodejs"></a>
### File-Based Storage (Node.js)

```typescript
import { KorrektlyClient } from '@korrektly/sdk';
import fs from 'fs/promises';
import path from 'path';
import { randomUUID } from 'crypto';

const getOrCreateInstanceId = async (): Promise<string> => {
  const configDir = path.join(process.cwd(), '.korrektly');
  const instanceFile = path.join(configDir, 'instance-id.txt');
  
  try {
    // Try to read existing instance ID
    const instanceId = await fs.readFile(instanceFile, 'utf-8');
    return instanceId.trim();
  } catch (error) {
    // Create new instance ID if file doesn't exist
    const newInstanceId = randomUUID();
    
    // Ensure directory exists
    await fs.mkdir(configDir, { recursive: true });
    
    // Write new instance ID
    await fs.writeFile(instanceFile, newInstanceId);
    
    return newInstanceId;
  }
};

// Usage
const instanceId = await getOrCreateInstanceId();
const korrektly = new KorrektlyClient({
  appId: 'my-node-app',
  instanceId,
  appVersion: '1.0.0'
});
```

<a id="sqlite-with-bun"></a>
### SQLite with Bun

```typescript
import { KorrektlyClient } from '@korrektly/sdk';
import { Database } from 'bun:sqlite';
import { randomUUID } from 'crypto';

const getOrCreateInstanceId = (): string => {
  const db = new Database('app.db');
  
  // Create table if it doesn't exist
  db.exec(`
    CREATE TABLE IF NOT EXISTS app_config (
      key TEXT PRIMARY KEY,
      value TEXT NOT NULL
    )
  `);
  
  // Try to get existing instance ID
  const query = db.query('SELECT value FROM app_config WHERE key = ?');
  const row = query.get('instance_id') as { value: string } | null;
  
  if (row) {
    db.close();
    return row.value;
  }
  
  // Create new instance ID
  const newInstanceId = randomUUID();
  const insert = db.query('INSERT INTO app_config (key, value) VALUES (?, ?)');
  insert.run('instance_id', newInstanceId);
  
  db.close();
  return newInstanceId;
};

const korrektly = new KorrektlyClient({
  appId: 'my-bun-sqlite-app',
  instanceId: getOrCreateInstanceId(),
  appVersion: '1.0.0'
});
```

<a id="prisma-example"></a>
### Prisma Example

```typescript
import { KorrektlyClient } from '@korrektly/sdk';
import { PrismaClient } from '@prisma/client';
import { randomUUID } from 'crypto';

const prisma = new PrismaClient();

const getOrCreateInstanceId = async (): Promise<string> => {
  // Try to get existing instance ID
  const config = await prisma.appConfig.findUnique({
    where: { key: 'instance_id' }
  });
  
  if (config) {
    return config.value;
  }
  
  // Create new instance ID
  const newInstanceId = randomUUID();
  await prisma.appConfig.create({
    data: {
      key: 'instance_id',
      value: newInstanceId
    }
  });
  
  return newInstanceId;
};

// Usage
const instanceId = await getOrCreateInstanceId();
const korrektly = new KorrektlyClient({
  appId: 'my-prisma-app',
  instanceId,
  appVersion: '1.0.0'
});

// Prisma schema example:
// model AppConfig {
//   key   String @id
//   value String
// }
```

<a id="drizzle-orm-with-postgresql"></a>
### Drizzle ORM with PostgreSQL

```typescript
import { KorrektlyClient } from '@korrektly/sdk';
import { drizzle } from 'drizzle-orm/postgres-js';
import { pgTable, text } from 'drizzle-orm/pg-core';
import { eq } from 'drizzle-orm';
import postgres from 'postgres';
import { randomUUID } from 'crypto';

// Define schema
const appConfig = pgTable('app_config', {
  key: text('key').primaryKey(),
  value: text('value').notNull(),
});

const client = postgres(process.env.DATABASE_URL!);
const db = drizzle(client);

const getOrCreateInstanceId = async (): Promise<string> => {
  // Try to get existing instance ID
  const result = await db
    .select()
    .from(appConfig)
    .where(eq(appConfig.key, 'instance_id'))
    .limit(1);
  
  if (result.length > 0) {
    return result[0].value;
  }
  
  // Create new instance ID
  const newInstanceId = randomUUID();
  await db.insert(appConfig).values({
    key: 'instance_id',
    value: newInstanceId
  });
  
  return newInstanceId;
};

// Usage
const instanceId = await getOrCreateInstanceId();
const korrektly = new KorrektlyClient({
  appId: 'my-drizzle-postgres-app',
  instanceId,
  appVersion: '1.0.0'
});
```

<a id="neon-database-example"></a>
### Neon Database Example

```typescript
import { KorrektlyClient } from '@korrektly/sdk';
import { neon } from '@neondatabase/serverless';
import { randomUUID } from 'crypto';

const sql = neon(process.env.DATABASE_URL!);

const getOrCreateInstanceId = async (): Promise<string> => {
  // Create table if it doesn't exist
  await sql`
    CREATE TABLE IF NOT EXISTS app_config (
      key TEXT PRIMARY KEY,
      value TEXT NOT NULL
    )
  `;
  
  // Try to get existing instance ID
  const result = await sql`
    SELECT value FROM app_config WHERE key = 'instance_id'
  `;
  
  if (result.length > 0) {
    return result[0].value;
  }
  
  // Create new instance ID
  const newInstanceId = randomUUID();
  await sql`
    INSERT INTO app_config (key, value) VALUES ('instance_id', ${newInstanceId})
  `;
  
  return newInstanceId;
};

// Usage
const instanceId = await getOrCreateInstanceId();
const korrektly = new KorrektlyClient({
  appId: 'my-neon-app',
  instanceId,
  appVersion: '1.0.0'
});
```

<a id="turso-example"></a>
### Turso Example

```typescript
import { KorrektlyClient } from '@korrektly/sdk';
import { createClient } from '@libsql/client';
import { randomUUID } from 'crypto';

const client = createClient({
  url: process.env.TURSO_DATABASE_URL!,
  authToken: process.env.TURSO_AUTH_TOKEN!,
});

const getOrCreateInstanceId = async (): Promise<string> => {
  // Create table if it doesn't exist
  await client.execute(`
    CREATE TABLE IF NOT EXISTS app_config (
      key TEXT PRIMARY KEY,
      value TEXT NOT NULL
    )
  `);
  
  // Try to get existing instance ID
  const result = await client.execute({
    sql: 'SELECT value FROM app_config WHERE key = ?',
    args: ['instance_id']
  });
  
  if (result.rows.length > 0) {
    return result.rows[0].value as string;
  }
  
  // Create new instance ID
  const newInstanceId = randomUUID();
  await client.execute({
    sql: 'INSERT INTO app_config (key, value) VALUES (?, ?)',
    args: ['instance_id', newInstanceId]
  });
  
  return newInstanceId;
};

// Usage
const instanceId = await getOrCreateInstanceId();
const korrektly = new KorrektlyClient({
  appId: 'my-turso-app',
  instanceId,
  appVersion: '1.0.0'
});
```

<a id="vercel-kv-example"></a>
### Vercel KV Example

```typescript
import { KorrektlyClient } from '@korrektly/sdk';
import { kv } from '@vercel/kv';
import { randomUUID } from 'crypto';

const getOrCreateInstanceId = async (): Promise<string> => {
  // Try to get existing instance ID
  const existingId = await kv.get('korrektly:instance_id');
  
  if (existingId) {
    return existingId as string;
  }
  
  // Create new instance ID
  const newInstanceId = randomUUID();
  await kv.set('korrektly:instance_id', newInstanceId);
  
  return newInstanceId;
};

// Usage in Vercel serverless function or Edge Runtime
const instanceId = await getOrCreateInstanceId();
const korrektly = new KorrektlyClient({
  appId: 'my-vercel-app',
  instanceId,
  appVersion: '1.0.0'
});
```

<a id="how-it-works"></a>
## How It Works

1. **Automatic Tracking**: When you create a `KorrektlyClient` instance, it automatically sends installation data to the Korrektly platform
2. **Instance Identification**: Each installation is tracked using the `instanceId` you provide
3. **Version Tracking**: Application versions are tracked to understand update patterns
4. **Privacy First**: Only the data you explicitly configure is sent (app ID, instance ID, and version)

<a id="api-reference"></a>
## API Reference

### KorrektlyClient

#### Constructor

```typescript
new KorrektlyClient(options: KorrektlyClientOptions)
```

Creates a new Korrektly client instance and automatically tracks the installation.

#### Options Interface

```typescript
interface KorrektlyClientOptions {
  appId: string;
  instanceId: string;
  appVersion?: string;
  disabled?: boolean;
  debug?: boolean;
}
```

<a id="debug-mode"></a>
## Debug Mode

When `debug: true` is set, the SDK will log information to the console:

```typescript
const korrektly = new KorrektlyClient({
  appId: 'my-app',
  instanceId: 'instance-123',
  debug: true
});

// Console output:
// (2024-01-15T10:30:45.123Z) [Korrektly] Installation data sent successfully | Status: 200 | Status Text: OK
```

<a id="error-handling"></a>
## Error Handling

The SDK handles errors gracefully and will not throw exceptions that could break your application:

- Network failures are logged (in debug mode) but don't interrupt your app
- API errors are logged (in debug mode) but don't interrupt your app
- Invalid configurations are handled silently

<a id="disabling-tracking"></a>
## Disabling Tracking

You can disable tracking entirely by setting `disabled: true`:

```typescript
const korrektly = new KorrektlyClient({
  appId: 'my-app',
  instanceId: 'instance-123',
  disabled: process.env.NODE_ENV === 'development'
});
```

<a id="changelog"></a>
## Changelog

See [CHANGELOG.md](CHANGELOG.md) for the latest updates.

<a id="contributing"></a>
## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

<a id="testing"></a>
## Testing

```bash
# Run tests
bun test

# Run tests with coverage
bun test --coverage
```

<a id="license"></a>
## License

MIT License - see the [LICENSE](LICENSE) file for details.