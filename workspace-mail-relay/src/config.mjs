function required(name) {
  const value = process.env[name]?.trim();
  if (!value) throw new Error(`${name} is required`);
  return value;
}

function integer(name, fallback, minimum, maximum) {
  const value = Number(process.env[name] ?? fallback);
  if (!Number.isSafeInteger(value) || value < minimum || value > maximum) {
    throw new Error(`${name} must be an integer from ${minimum} to ${maximum}`);
  }
  return value;
}

export function loadConfig() {
  const config = {
    port: integer("PORT", 8082, 1, 65535),
    databasePath: required("DATABASE_PATH"),
    googleOauthClientFile: required("GOOGLE_OAUTH_CLIENT_FILE"),
    googleOauthTokenFile: required("GOOGLE_OAUTH_TOKEN_FILE"),
    fromEmail: required("MAIL_FROM_EMAIL").toLowerCase(),
    fromName: required("MAIL_FROM_NAME"),
    replyTo: required("MAIL_REPLY_TO").toLowerCase(),
    dailySafetyLimit: integer("DAILY_SAFETY_LIMIT", 1000, 1, 10000),
    hourlySafetyLimit: integer("HOURLY_SAFETY_LIMIT", 300, 1, 5000),
    minSendIntervalMs: integer("MIN_SEND_INTERVAL_MS", 3000, 250, 60000),
    clientHashes: parseClients(required("RELAY_CLIENTS")),
  };
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(config.fromEmail)) throw new Error("MAIL_FROM_EMAIL must be an email address");
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(config.replyTo)) throw new Error("MAIL_REPLY_TO must be an email address");
  if (/[\r\n]/.test(config.fromName)) throw new Error("MAIL_FROM_NAME must be one line");
  return config;
}

function parseClients(value) {
  const clients = new Map();
  for (const item of value.split(",")) {
    const [id, hash, ...extra] = item.trim().split(":");
    if (extra.length || !/^[a-z][a-z0-9_-]{2,40}$/.test(id || "") || !/^[a-f0-9]{64}$/.test(hash || "")) {
      throw new Error("RELAY_CLIENTS must contain client_id:sha256(secret) entries");
    }
    if (clients.has(id)) throw new Error(`Duplicate relay client: ${id}`);
    clients.set(id, hash);
  }
  return clients;
}
