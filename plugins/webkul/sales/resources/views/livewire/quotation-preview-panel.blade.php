<div class="quotation-preview-panel">
    <div class="bg-white border border-gray-300 rounded-lg shadow-sm">
        <div class="sticky top-0 bg-gray-50 border-b border-gray-200 px-4 py-3 z-10">
            <h3 class="text-sm font-semibold text-gray-700 flex items-center">
                <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                Template Preview (Sample Data)
            </h3>
        </div>

        {{-- Use iframe to fully isolate template styles from the page --}}
        <div class="p-4">
            <iframe
                id="template-preview-iframe"
                class="w-full border-0"
                style="height: 800px;"
                srcdoc="{{ htmlspecialchars($this->renderedTemplate, ENT_QUOTES) }}"
            ></iframe>
        </div>
    </div>

    <script>
        // Auto-adjust iframe height to content
        document.addEventListener('DOMContentLoaded', function() {
            const iframe = document.getElementById('template-preview-iframe');
            if (iframe) {
                iframe.onload = function() {
                    try {
                        const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                        iframe.style.height = iframeDoc.body.scrollHeight + 50 + 'px';
                    } catch (e) {
                        console.log('Could not adjust iframe height:', e);
                    }
                };
            }
        });
    </script>
</div>
