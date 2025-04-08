const mysql = require('mysql2/promise');

let connection;

module.exports = {
    async connect(config) {
        connection = await mysql.createConnection(config);
        console.log('Database connected');
    },
    async getTables() {
        const [rows] = await connection.query('SHOW TABLES');
        return rows.map((row) => Object.values(row)[0]);
    },
    async getColumns(table) {
        const [rows] = await connection.query(`SHOW COLUMNS FROM ${table}`);
        return rows.map((row) => row.Field);
    },
    async executeQuery(query) {
        const [rows] = await connection.query(query);
        return rows;
    },
    async close() {
        await connection.end();
        console.log('Database connection closed');
    },
    async getDatabaseStructure() {
        const tables = await this.getTables();
        const dbStructure = await Promise.all(
            tables.map(async (table) => ({
                table,
                columns: await this.getColumns(table),
            }))
        );
        return dbStructure;
    },
    async getTableData(table) {
        const [rows] = await connection.query(`SELECT * FROM ${table}`);
        return rows;
    },
    async getTableCount(table) {
        const [rows] = await connection.query(`SELECT COUNT(*) AS count FROM ${table}`);
        return rows[0].count;
    },
    async getTableSchema(table) {
        const [rows] = await connection.query(`SHOW CREATE TABLE ${table}`);
        return rows[0]['Create Table'];
    },
    async getTableIndexes(table) {
        const [rows] = await connection.query(`SHOW INDEX FROM ${table}`);
        return rows.map((row) => ({
            key_name: row.Key_name,
            column_name: row.Column_name,
            non_unique: row.Non_unique,
        }));
    },
    async getTableForeignKeys(table) {
        const [rows] = await connection.query(`SHOW CREATE TABLE ${table}`);
        return rows[0]['Create Table'].match(/FOREIGN KEY \(`(.+?)`\) REFERENCES `(.+?)` \(`(.+?)`\)/g) || [];
    },
    async getTableTriggers(table) {
        const [rows] = await connection.query(`SHOW TRIGGERS LIKE '${table}'`);
        return rows.map((row) => row.Trigger);
    },
    async getTableConstraints(table) {
        const [rows] = await connection.query(`SHOW CREATE TABLE ${table}`);
        return rows[0]['Create Table'].match(/CONSTRAINT `(.+?)` FOREIGN KEY \(`(.+?)`\) REFERENCES `(.+?)` \(`(.+?)`\)/g) || [];
    },
    async getTablePartitions(table) {
        const [rows] = await connection.query(`SHOW CREATE TABLE ${table}`);
        return rows[0]['Create Table'].match(/PARTITION BY (.+?)\(/g) || [];
    }
    // Add more database-related methods as needed
    // ...
};

