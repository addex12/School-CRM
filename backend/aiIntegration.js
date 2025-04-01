const express = require('express');
const axios = require('axios');
const router = express.Router();
const db = require('./db'); // Assuming a database module is available
const logger = require('./logger'); // Assuming a logger module is available

router.post('/getAISuggestions', async (req, res) => {
    const { codeSnippet, context } = req.body;

    try {
        // Fetch database structure with error handling
        let dbStructure;
        try {
            const tables = await db.getTables();
            dbStructure = await Promise.all(
                tables.map(async (table) => ({
                    table,
                    columns: await db.getColumns(table),
                }))
            );
        } catch (dbError) {
            console.error('Error fetching database structure:', dbError);
            return res.status(500).json({ error: 'Failed to fetch database structure' });
        }

        const extendedContext = `${context}\n\nDatabase Structure:\n${JSON.stringify(dbStructure, null, 2)}`;

        const response = await axios.post('https://api.openai.com/v1/completions', {
            model: 'text-davinci-003',
            prompt: `Suggest improvements for the following code:\n\n${extendedContext}\n\n${codeSnippet}`,
            max_tokens: 200,
        }, {
            headers: {
                'Authorization': `Bearer YOUR_API_KEY`,
            },
        });

        const suggestions = response.data.choices[0].text;
        if (!suggestions) {
            return res.status(500).json({ error: 'No suggestions received from AI' });
        }
        // Log the request and response
        logger.log('AI Request', { codeSnippet, context });
        logger.log('AI Response', { suggestions });
        // Log the database structure
        logger.log('Database Structure', { dbStructure });
        // Log the extended context
        logger.log('Extended Context', { extendedContext });
        // Log the AI response
        logger.log('AI Response', { suggestions });
        // Log the original code snippet
        logger.log('Original Code Snippet', { codeSnippet });
        // Log the context
        logger.log('Context', { context });
        // Log the AI model used
        logger.log('AI Model', { model: 'text-davinci-003' });
        // Log the API key used (Note: In a real application, never log API keys)
        logger.log('API Key', { key: 'YOUR_API_KEY' });
        // Log the AI suggestions

        // Log the suggestions
        logger.log('AI Suggestions', { codeSnippet, suggestions });

        res.json({ suggestions });
    } catch (error) {
        console.error('Error fetching AI suggestions:', error);
        res.status(500).json({ error: 'Failed to fetch AI suggestions' });
    }
});

module.exports = router;
