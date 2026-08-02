import nodemailer from "nodemailer";
import { createServer } from "./app.mjs";
import { loadConfig } from "./config.mjs";
import { openStore } from "./store.mjs";

const config = loadConfig();
const store = openStore(config.databasePath);
const mailer = nodemailer.createTransport({
  host: config.smtpHost,
  port: config.smtpPort,
  secure: false,
  requireTLS: true,
  name: config.smtpHeloName,
  connectionTimeout: 10_000,
  greetingTimeout: 10_000,
  socketTimeout: 30_000,
  disableFileAccess: true,
  disableUrlAccess: true,
  tls: { minVersion: "TLSv1.2", servername: config.smtpHost },
});
const server = createServer({ config, store, mailer });

server.listen(config.port, "127.0.0.1", () => {
  console.log(`Workspace mail relay listening on 127.0.0.1:${config.port}`);
});

function shutdown() {
  server.close(() => {
    store.close();
    process.exit(0);
  });
}
process.on("SIGTERM", shutdown);
process.on("SIGINT", shutdown);
