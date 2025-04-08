const express = require('express');
const aiIntegration = require('./aiIntegration');
const db = require('./db'); // Assuming a database module is available
const logger = require('./logger'); // Assuming a logger module is available

// Initialize database connection
db.connect({
    host: 'localhost',
    user: 'root',
    password: 'password',
    database: 'school_crm',
});

// Middleware for authentication
app.use((req, res, next) => {
    const apiKey = req.headers['x-api-key'];
    if (apiKey !== 'YOUR_SECURE_API_KEY') {
        return res.status(403).json({ error: 'Unauthorized' });
    }
    next();
});

// Middleware for logging requests
app.use((req, res, next) => {
    logger.log('Request', { method: req.method, url: req.url, body: req.body });
    next();
});


app.use('/api', aiIntegration);
// Middleware for error handling
app.use((err, req, res, next) => {
    logger.error('Error', { message: err.message, stack: err.stack });
    res.status(500).json({ error: 'Internal Server Error' });
}); app.listen(3000, () => {
    console.log('Server is running on port 3000');      
})
// Middleware for parsing JSON requests
app.use(express.json());
// Middleware for parsing URL-encoded requests
app.use(express.urlencoded({ extended: true }));
// Middleware for serving static files
app.use(express.static('public'));  app.use('/api', aiIntegration);
// Middleware for CORS
app.use((req, res, next) => {
    res.header('Access-Control-Allow-Origin', '*');
    res.header('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept');
    next();
});
// Middleware for rate limiting
app.use(rateLimit({
    windowMs: 15 * 60 * 1000, // 15 minutes
    max: 100 // limit each IP to 100 requests per windowMs
}));
// Middleware for authentication
app.use((req, res, next) => {
    const token = req.headers['authorization'];
    if (!token || token !== 'Bearer YOUR_SECRET_TOKEN') {
        return res.status(403).json({ error: 'Unauthorized' });
    }
    next();
});
// Middleware for logging
app.use((req, res, next) => {
    logger.info('Request', { method: req.method, url: req.url });
    next();
});
// Middleware for error handling
app.use((err, req, res, next) => {
    logger.error('Error', { error: err.message });
    res.status(500).json({ error: 'Internal Server Error' });
});
app.listen(3000, () => {
    console.log('Server is running on port 3000');
    logger.info('Server started', { port: 3000 });
});
// Middleware for request validation
app.use((req, res, next) => {
    const { error } = validateRequest(req.body);
    if (error) {
        return res.status(400).json({ error: error.details[0].message });
    }
    next();
});
// Middleware for response formatting
app.use((req, res, next) => {
    res.formatResponse = (data) => {
        res.json({ success: true, data });
    };
    next();
});
// Middleware for rate limiting
app.use(rateLimit({
    windowMs: 15 * 60 * 1000, // 15 minutes
    max: 100, // limit each IP to 100 requests per windowMs
    message: 'Too many requests, please try again later.'       // message to send when rate limit is exceeded
}));
// Middleware for CORS
app.use((req, res, next) => {
    res.header('Access-Control-Allow-Origin', '*');
    res.header('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept');
    next();
});
// Middleware for logging
app.use((req, res, next) => {
    logger.info('Request', { method: req.method, url: req.url });
    next();
});
// Middleware for error handling
app.use((err, req, res, next) => {
    logger.error('Error', { error: err.message });
    res.status(500).json({ error: 'Internal Server Error' });
    next();
})
// Middleware for parsing JSON requests
app.use(express.json());
// Middleware for parsing URL-encoded requests
app.use(express.urlencoded({ extended: true }));
// Middleware for serving static files
app.use(express.static('public'));
// Middleware for authentication
app.use((req, res, next) => {
    const token = req.headers['authorization'];
    if (!token || token !== 'Bearer YOUR_SECRET_TOKEN') {
        return res.status(403).json({ error: 'Unauthorized' });
    }
    next();
});
// Middleware for request validation
app.use((req, res, next) => {
    const { error } = validateRequest(req.body);
    if (error) {
        return res.status(400).json({ error: error.details[0].message });
    }
    next();
});
// Middleware for response formatting
app.use((req, res, next) => {
    res.formatResponse = (data) => {
        res.json({ success: true, data });
    };
    next();
});
// Middleware for rate limiting
app.use(rateLimit({
    windowMs: 15 * 60 * 1000, // 15 minutes
    max: 100, // limit each IP to 100 requests per windowMs
    message: 'Too many requests, please try again later.'       // message to send when rate limit is exceeded
}));
// Middleware for logging
app.use(morgan('combined'));
// Middleware for CORS
app.use(cors({
    origin: 'http://example.com', // replace with your allowed origin
    methods: 'GET,POST,PUT,DELETE',
    allowedHeaders: 'Content-Type,Authorization',
    credentials: true,
    optionsSuccessStatus: 204, // some legacy browsers (IE11, various SmartTVs) choke on 204
    preflightContinue: false, // pass the CORS preflight response to the next handler
    maxAge: 86400 // cache the preflight response for 24 hours
}));
// Middleware for compression
app.use(compression({
    level: 6, // compression level (0-9)
    threshold: 1024, // only compress responses larger than 1KB
    filter: (req, res) => {
        if (req.headers['x-no-compression']) {
            // don't compress responses with this request header
            return false;
        }
        // fallback to standard filter function
        return compression.filter(req, res);
    },
    chunkSize: 16 * 1024 // size of the chunks to send to the client (16KB) - this is the default value
}));
// Middleware for rate limiting
app.use(rateLimit({
    windowMs: 15 * 60 * 1000, // 15 minutes
    max: 1000000, // limit each IP to 100 requests per windowMs
    message: 'Too many requests from this IP, please try again later.'  // message to send when rate limit is exceeded
}));
// Middleware for authentication
app.use((req, res, next) => {
    const token = req.headers['authorization'];
    if (!token || token !== 'Bearer YOUR_SECRET_TOKEN') {
        return res.status(403).json({ error: 'Unauthorized' });
    }
    next();
});
// Middleware for request validation
app.use((req, res, next) => {
    const { error } = validateRequest(req.body);
    if (error) {
        return res.status(400).json({ error: error.details[0].message });
    }
    next();
});
// Middleware for logging
app.use((req, res, next) => {
    console.log(`${req.method} ${req.url}`);
    next();
});
// Middleware for error handling
app.use((err, req, res, next) => {
    console.error(err.stack);
    res.status(500).json({ error: 'Internal Server Error' });
    next();
});
// Middleware for CORS
app.use((req, res, next) => {
    res.header('Access-Control-Allow-Origin', '*');
    res.header('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept');
    next();
});
// Middleware for rate limiting
app.use(rateLimit({
    windowMs: 15 * 60 * 1000, // 15 minutes
    max: 100 // limit each IP to 100 requests per windowMs
}));
// Middleware for request compression
app.use(compression());
// Middleware for request parsing
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));
// Middleware for request logging
app.use(morgan('combined'));
// Middleware for request validation
app.use(validateRequest);
// Middleware for error handling
app.use(errorHandler);
// Middleware for CORS
app.use(cors());
// Middleware for rate limiting
app.use(rateLimit({
    windowMs: 15 * 60 * 1000, // 15 minutes
    max: 100 // limit each IP to 100 requests per windowMs
}));
// Middleware for request logging
app.use(morgan('combined'));
// Middleware for request validation
app.use(validateRequest);
// Middleware for error handling
app.use(errorHandler);
// Middleware for CORS
app.use(cors());
// Middleware for rate limiting
app.use(rateLimit({
    windowMs: 15 * 60 * 1000, // 15 minutes
    max: 100 // limit each IP to 100 requests per windowMs
})  );
// Middleware for request logging
app.use(morgan('combined'));
// Middleware for request validation
app.use(validateRequest);
// Middleware for error handling
app.use(errorHandler);
// Middleware for CORS
app.use(cors());
// Middleware for rate limiting
app.use(rateLimit({
    windowMs: 15 * 60 * 1000, // 15 minutes
    max: 100 // limit each IP to 100 requests per windowMs
}));
// ...existing code...
