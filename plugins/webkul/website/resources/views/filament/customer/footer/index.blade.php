  <footer class="bg-gradient-to-r from-blue-50 via-purple-50 to-blue-100 px-6 py-12">
		<div class="container mx-auto max-w-6xl">
			<div class="grid grid-cols-1 gap-8 md:grid-cols-3">
				<!-- Logo and Description Column -->
				<div class="md:col-span-1">
					<div class="mb-6">
						<x-filament-panels::logo class="mb-4" />
					</div>

					<p class="mb-4 text-gray-700">
						Designed to optimize and streamline business operations, Aureus ERP is suitable for enterprises of all sizes.
					</p>
					
					<p class="text-gray-700">
						The platform emphasizes reporting for insights, security, localization flexibility, and integration with CRMs, BI tools, and APIs.
					</p>
				</div>

				<!-- Useful Links Column -->
				<div class="md:col-span-1">
					<h3 class="mb-4 text-lg font-medium">Useful Links</h3>
					
					<ul class="space-y-2">
						@foreach ($navigationItems as $item)
							<li>
								<a href="{{ $item->getUrl() }}" class="text-gray-700 hover:text-primary-600">
									{{ $item->getLabel() }}
								</a>
							</li>
						@endforeach
					</ul>
				</div>
				
				<!-- Contact and Social Media Column -->
				<div class="md:col-span-1">
					<h3 class="mb-4 text-lg font-medium">Contact Us</h3>

					<div class="mb-2">
						<a href="mailto:support@webkul.com" class="flex items-center text-gray-700 hover:text-primary-600">
							<svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
								<path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
								<path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
							</svg>

							support@webkul.com
						</a>
					</div>
					
					<div class="mb-6">
						<a href="tel:9876543210" class="flex items-center text-gray-700 hover:text-primary-600">
							<svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
								<path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
							</svg>

							9876543210
						</a>
					</div>
				
					<h3 class="mb-4 text-lg font-medium">Follow Us</h3>
					
					<div class="flex space-x-3">
						<a href="#" class="rounded-full bg-gray-800 p-2 text-white hover:bg-primary-600">
							<svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
								<path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"></path>
							</svg>
						</a>

						<a href="#" class="rounded-full bg-gray-800 p-2 text-white hover:bg-primary-600">
							<svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
								<path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"></path>
							</svg>
						</a>

						<a href="#" class="rounded-full bg-gray-800 p-2 text-white hover:bg-primary-600">
							<svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
								<path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm5.888 14.12c-.23.007-.461.007-.691.007-1.28 0-2.561-.137-3.779-.407-1.325-.296-2.604-.854-3.686-1.674a8.472 8.472 0 01-2.307-2.64 8.081 8.081 0 01-1.174-3.05 9.52 9.52 0 01-.07-2.301c.072-.83.283-1.653.631-2.404a7.63 7.63 0 011.922-2.416A8.57 8.57 0 0111.55 2.21a9.98 9.98 0 012.5-.252c.83.039 1.648.195 2.432.457a8.89 8.89 0 012.896 1.491c1.527 1.186 2.755 2.682 3.375 4.58.418 1.23.57 2.57.445 3.878-.118 1.318-.51 2.575-1.153 3.646-.757 1.255-1.76 2.255-2.92 2.996-.823.497-1.75.778-2.695.897-.258.033-.517.05-.777.05-.258 0-.516-.017-.775-.05zm.705-13.45a7.29 7.29 0 00-3.89-.607c-1.596.178-3.137.981-4.297 2.175a7.185 7.185 0 00-1.88 3.22 7.587 7.587 0 00-.107 2.79c.16 1.3.703 2.527 1.546 3.525.705.831 1.625 1.474 2.648 1.845.772.281 1.596.402 2.408.344 1.1-.077 2.143-.51 2.98-1.196a6.423 6.423 0 001.91-2.626c.394-.92.576-1.947.52-2.962a6.332 6.332 0 00-.709-2.61 6.822 6.822 0 00-1.13-1.701z"></path>
							</svg>
						</a>
						
						<a href="#" class="rounded-full bg-gray-800 p-2 text-white hover:bg-primary-600">
							<svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
								<path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"></path>
							</svg>
						</a>
					</div>
				</div>
			</div>
			
			<!-- Copyright Section -->
			<div class="mt-8 flex flex-col justify-between border-t border-gray-200 pt-8 md:flex-row">
				<div class="text-sm text-gray-600">
					Copyright © AureusERP
				</div>
				
				<div class="mt-2 text-sm text-gray-600 md:mt-0">
					Powered by : Webkul Software
				</div>
			</div>
		</div>
  </footer>