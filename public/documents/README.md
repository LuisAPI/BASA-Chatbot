# Default Government Documents

This directory contains essential government documents that are bundled with every instance of the BASA chatbot. These documents provide the core knowledge base for the chatbot's responses about government policies, plans, and procedures.

## Purpose

These documents serve as the foundational knowledge base for the chatbot, ensuring that every instance has access to critical government information without requiring manual uploads.

## Document Categories

### Economic Development Plans
- **Philippine Development Plan (PDP) 2023-2028** - The country's medium-term development plan
- Other economic planning documents as needed

### Government Policies
- Key policy documents relevant to DEPDev's mandate
- Administrative orders and circulars

### Reference Materials
- Organizational charts and structures
- Standard operating procedures
- Frequently referenced government documents

## Directory Structure

You can organize documents in subdirectories for better organization:

```
public/documents/
├── pdp/
│   ├── pdp-2023-2028.pdf
│   └── pdp-2017-2022.pdf
├── oc/
│   ├── office-circular-2024-001.pdf
│   └── office-circular-2024-002.pdf
├── policies/
│   └── government-policy-2024.pdf
└── README.md
```

The system will recursively scan all subdirectories and process all PDF files found.

## File Naming Convention

Use descriptive, lowercase filenames with hyphens:
- `pdp-2023-2028.pdf`
- `government-policy-2024.pdf`
- `depdev-organizational-structure.pdf`

## Processing

These documents are automatically processed by the `ProcessDefaultDocuments` Artisan command, which:
1. Chunks the documents into searchable segments
2. Generates embeddings for semantic search
3. Stores the chunks in the RAG system

## Initial Setup

For new installations, you can process all default documents using:

```sh
# Option A: Use the comprehensive setup command (recommended)
php artisan setup:basa

# Option B: Process documents only
php artisan documents:process-default
```

## Adding New Documents

1. Place the document file in this directory (or subdirectories)
2. Run `php artisan documents:process-default` to process the new document
3. The document will be available in the chatbot immediately

## Access

These documents are accessible through:
- The chatbot's RAG system for answering questions
- The File Gallery (marked as "System Documents")
- Direct file access via web URLs (if needed)

## Version Control

All documents in this directory are version-controlled and will be deployed with the application. Updates to documents should be committed to the repository. 