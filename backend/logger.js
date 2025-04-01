const fs = require('fs');
const path = require('path');

const logFile = path.join(__dirname, 'logs.txt');

module.exports = {
    log(type, data) {
        const timestamp = new Date().toISOString();
        const logEntry = `[${timestamp}] [${type}] ${JSON.stringify(data)}\n`;
        fs.appendFileSync(logFile, logEntry, 'utf8');
    },
};
const { createLogger, format, transports } = require('winston');
const path = require('path');
const fs = require('fs');
const logDir = path.join(__dirname, 'logs');
// Create log directory if it doesn't exist
if (!fs.existsSync(logDir)) {
    fs.mkdirSync(logDir);
}
// Create a logger instance
const logger = createLogger({
    level: 'info',
    format: format.combine(
        format.timestamp({ format: 'YYYY-MM-DD HH:mm:ss' }),
        format.errors({ stack: true }),
        format.json()
    ),
    transports: [
        new transports.File({ filename: path.join(logDir, 'error.log'), level: 'error' }),
        new transports.File({ filename: path.join(logDir, 'combined.log') }),
        new transports.Console({
            format: format.combine(
                format.colorize(),
                format.simple()
            )
        })
    ]
});
module.exports = [
    logger,
    {
        log(type, data) {
            logger.info(`[${type}] ${JSON.stringify(data)}`);
        },
    },
]
// Export the logger
module.exports = logger;
