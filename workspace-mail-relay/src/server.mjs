import { createServer } from "./app.mjs";
import { loadConfig } from "./config.mjs";
import { createGmailApiMailer } from "./gmail-api.mjs";
import { openStore } from "./store.mjs";

const config = loadConfig();
const store = openStore(config.databasePath);
const mailer = createGmailApiMailer(config);
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
