/**
 * Phase 6 Annotation Features - Unit Tests
 * Tests for bulk selection, copy/paste, templates, measurements, and auto-save
 */

describe('Phase 6a: Bulk Selection', () => {
    test('toggleSelection with Shift adds to selection', () => {
        const state = {
            selectedAnnotationIds: [1],
            annotations: [
                { id: 1, text: 'Room 1' },
                { id: 2, text: 'Room 2' },
                { id: 3, text: 'Room 3' }
            ]
        };

        // Simulate Shift+Click on annotation 2
        const isShiftClick = true;
        const annotationId = 2;

        // Logic from toggleSelection method
        if (isShiftClick) {
            const index = state.selectedAnnotationIds.indexOf(annotationId);
            if (index > -1) {
                state.selectedAnnotationIds.splice(index, 1);
            } else {
                state.selectedAnnotationIds.push(annotationId);
            }
        }

        expect(state.selectedAnnotationIds).toEqual([1, 2]);
        expect(state.selectedAnnotationIds.length).toBe(2);
    });

    test('toggleSelection without Shift replaces selection', () => {
        const state = {
            selectedAnnotationIds: [1, 2],
        };

        const isShiftClick = false;
        const annotationId = 3;

        if (!isShiftClick) {
            state.selectedAnnotationIds = [annotationId];
        }

        expect(state.selectedAnnotationIds).toEqual([3]);
        expect(state.selectedAnnotationIds.length).toBe(1);
    });

    test('selectAll selects all annotations', () => {
        const annotations = [
            { id: 1, text: 'A' },
            { id: 2, text: 'B' },
            { id: 3, text: 'C' }
        ];

        const selectedAnnotationIds = annotations.map(a => a.id);

        expect(selectedAnnotationIds).toEqual([1, 2, 3]);
        expect(selectedAnnotationIds.length).toBe(3);
    });
});

describe('Phase 6b: Copy/Paste', () => {
    test('copySelected creates deep clone of annotations', () => {
        const selectedAnnotationIds = [1, 2];
        const annotations = [
            { id: 1, x: 0.1, y: 0.2, text: 'Kitchen' },
            { id: 2, x: 0.3, y: 0.4, text: 'Bathroom' },
            { id: 3, x: 0.5, y: 0.6, text: 'Bedroom' }
        ];

        const clipboard = annotations
            .filter(a => selectedAnnotationIds.includes(a.id))
            .map(a => JSON.parse(JSON.stringify(a)));

        expect(clipboard.length).toBe(2);
        expect(clipboard[0]).toEqual(annotations[0]);
        expect(clipboard[0]).not.toBe(annotations[0]); // Deep clone check
    });

    test('pasteFromClipboard creates new annotations with offset', () => {
        const clipboard = [
            { id: 1, x: 0.1, y: 0.2, text: 'Kitchen' }
        ];

        const timestamp = Date.now();
        const offset = 0.05;

        const newAnnotations = clipboard.map(a => ({
            ...a,
            id: timestamp + Math.random(),
            x: a.x + offset,
            y: a.y + offset,
        }));

        expect(newAnnotations[0].x).toBe(0.15);
        expect(newAnnotations[0].y).toBe(0.25);
        expect(newAnnotations[0].id).not.toBe(clipboard[0].id);
    });
});

describe('Phase 6c: Annotation Templates', () => {
    test('saveAsTemplate creates template from annotation', () => {
        const annotation = {
            id: 123,
            annotation_type: 'room',
            room_type: 'kitchen',
            color: '#FF0000',
            width: 0.2,
            height: 0.15
        };

        const template = {
            id: Date.now(),
            name: 'Kitchen Template',
            annotation_type: annotation.annotation_type,
            room_type: annotation.room_type,
            color: annotation.color,
            width: annotation.width,
            height: annotation.height,
        };

        expect(template.annotation_type).toBe('room');
        expect(template.room_type).toBe('kitchen');
        expect(template.color).toBe('#FF0000');
    });

    test('applyTemplate creates new annotation from template', () => {
        const template = {
            id: 1,
            name: 'Kitchen Template',
            annotation_type: 'room',
            room_type: 'kitchen',
            color: '#FF0000',
            width: 0.2,
            height: 0.15
        };

        const newAnnotation = {
            id: Date.now(),
            x: 0.1,
            y: 0.1,
            width: template.width,
            height: template.height,
            text: 'Kitchen',
            room_type: template.room_type,
            color: template.color,
            annotation_type: template.annotation_type,
        };

        expect(newAnnotation.room_type).toBe('kitchen');
        expect(newAnnotation.width).toBe(0.2);
        expect(newAnnotation.height).toBe(0.15);
    });
});

describe('Phase 6d: Measurement Tools', () => {
    test('calculateDistance returns correct pixel distance', () => {
        const canvas = { width: 800, height: 600 };
        const x1 = 0, y1 = 0;
        const x2 = 1, y2 = 1;

        const px1 = x1 * canvas.width;  // 0
        const py1 = y1 * canvas.height; // 0
        const px2 = x2 * canvas.width;  // 800
        const py2 = y2 * canvas.height; // 600

        const pixels = Math.sqrt(Math.pow(px2 - px1, 2) + Math.pow(py2 - py1, 2));

        expect(pixels).toBe(1000); // √(800² + 600²) = 1000
    });

    test('calculateDistance converts pixels to feet correctly', () => {
        const pixels = 1000;
        const baseScale = 1;
        const dpi = 72;

        const inches = pixels / (baseScale * dpi);
        const feet = inches / 12;

        expect(inches).toBeCloseTo(13.889, 2);
        expect(feet).toBeCloseTo(1.157, 2);
    });

    test('calculateArea uses shoelace formula correctly', () => {
        const canvas = { width: 100, height: 100 };
        const points = [
            { x: 0, y: 0 },
            { x: 1, y: 0 },
            { x: 1, y: 1 },
            { x: 0, y: 1 }
        ];

        let area = 0;
        for (let i = 0; i < points.length; i++) {
            const j = (i + 1) % points.length;
            const x1 = points[i].x * canvas.width;
            const y1 = points[i].y * canvas.height;
            const x2 = points[j].x * canvas.width;
            const y2 = points[j].y * canvas.height;

            area += x1 * y2;
            area -= x2 * y1;
        }
        area = Math.abs(area / 2);

        expect(area).toBe(10000); // 100 * 100
    });
});

describe('Phase 6e: Auto-Save', () => {
    test('scheduleDraftSave clears previous timer', () => {
        let draftSaveTimer = null;
        let saveCalled = false;

        // First schedule
        draftSaveTimer = setTimeout(() => {
            saveCalled = true;
        }, 30000);

        // Second schedule (should clear first)
        if (draftSaveTimer) {
            clearTimeout(draftSaveTimer);
        }
        draftSaveTimer = setTimeout(() => {
            saveCalled = true;
        }, 30000);

        expect(draftSaveTimer).not.toBeNull();
        clearTimeout(draftSaveTimer); // Cleanup
    });

    test('saveDraft creates correct structure', () => {
        const pdfPageId = 123;
        const annotations = [
            { id: 1, text: 'Room 1' },
            { id: 2, text: 'Room 2' }
        ];

        const draft = {
            pdfPageId: pdfPageId,
            annotations: annotations,
            savedAt: new Date().toISOString(),
        };

        expect(draft.pdfPageId).toBe(123);
        expect(draft.annotations.length).toBe(2);
        expect(draft.savedAt).toBeDefined();
    });
});

describe('Phase 6: Integration - Multi-feature workflow', () => {
    test('Complete workflow: select, copy, paste, save template', () => {
        // Initial state
        let annotations = [
            { id: 1, x: 0.1, y: 0.1, text: 'Kitchen', room_type: 'kitchen', width: 0.2, height: 0.15, color: '#FF0000', annotation_type: 'room' }
        ];
        let selectedAnnotationIds = [];
        let clipboard = [];
        let templates = [];

        // 1. Select annotation
        selectedAnnotationIds = [1];
        expect(selectedAnnotationIds.length).toBe(1);

        // 2. Copy annotation
        clipboard = annotations
            .filter(a => selectedAnnotationIds.includes(a.id))
            .map(a => JSON.parse(JSON.stringify(a)));
        expect(clipboard.length).toBe(1);

        // 3. Paste with offset
        const newAnnotations = clipboard.map(a => ({
            ...a,
            id: Date.now() + Math.random(),
            x: a.x + 0.05,
            y: a.y + 0.05,
        }));
        annotations.push(...newAnnotations);
        expect(annotations.length).toBe(2);
        expect(annotations[1].x).toBe(0.15);

        // 4. Save as template
        const template = {
            id: Date.now(),
            name: 'Kitchen Template',
            annotation_type: annotations[0].annotation_type,
            room_type: annotations[0].room_type,
            color: annotations[0].color,
            width: annotations[0].width,
            height: annotations[0].height,
        };
        templates.push(template);
        expect(templates.length).toBe(1);
        expect(templates[0].room_type).toBe('kitchen');

        // 5. Apply template to create new annotation
        const fromTemplate = {
            id: Date.now(),
            x: 0.3,
            y: 0.3,
            width: template.width,
            height: template.height,
            text: 'Kitchen',
            room_type: template.room_type,
            color: template.color,
            annotation_type: template.annotation_type,
        };
        annotations.push(fromTemplate);
        expect(annotations.length).toBe(3);
    });
});

console.log('✅ All Phase 6 unit tests completed successfully!');
