import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { dirname } from 'path';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

const schemasDir = path.join(__dirname, 'schemas');
const pathsDir = path.join(__dirname, 'paths');
const outputFile = path.join(__dirname, 'openapi.json');

// Load the main OpenAPI file
let mainSpec;
try {
  const mainSpecTemplate = fs.readFileSync(
    path.join(__dirname, 'openapi-template.json'),
    'utf8',
  );
  mainSpec = JSON.parse(mainSpecTemplate);

  if (!mainSpec.paths) mainSpec.paths = {};
  if (!mainSpec.components) mainSpec.components = {};
  if (!mainSpec.components.schemas) mainSpec.components.schemas = {};
} catch (error) {
  // basic structure
  mainSpec = {
    openapi: '3.0.3',
    info: {
      title: 'Launchpad API',
      version: '1.0.0',
    },
    paths: {},
    components: {
      schemas: {},
    },
  };
}

// Load all schema files
if (fs.existsSync(schemasDir)) {
  fs.readdirSync(schemasDir).forEach((file) => {
    if (path.extname(file) === '.json') {
      const schemaName = path.basename(file, '.json');
      try {
        const schemaContent = fs.readFileSync(
          path.join(schemasDir, file),
          'utf8',
        );
        const schemaData = JSON.parse(schemaContent);
        if (schemaData.type && (schemaData.properties || schemaData.items)) {
          mainSpec.components.schemas[schemaName] = schemaData;
          console.log(`✓ Added schema: ${schemaName}`);
        } else {
          Object.keys(schemaData).forEach((subSchemaName) => {
            mainSpec.components.schemas[subSchemaName] =
              schemaData[subSchemaName];
            console.log(`✓ Added schema: ${subSchemaName} from ${file}`);
          });
        }
      } catch (error) {
        console.error(`Error processing schema ${file}:`, error.message);
      }
    }
  });
}

// path files
if (fs.existsSync(pathsDir)) {
  fs.readdirSync(pathsDir).forEach((file) => {
    if (path.extname(file) === '.json') {
      try {
        const pathContent = fs.readFileSync(path.join(pathsDir, file), 'utf8');
        const pathObject = JSON.parse(pathContent);

        // Merge path objects
        Object.assign(mainSpec.paths, pathObject);
        console.log(`✓ Added paths from: ${file}`);
      } catch (error) {
        console.error(`Error processing path file ${file}:`, error.message);
      }
    }
  });
}

fs.writeFileSync(outputFile, JSON.stringify(mainSpec, null, 2));
console.log(`✓ Generated OpenAPI spec: ${outputFile}`);
