// Resolve backend API base URL with flexible fallbacks:
// 1) window.BACKEND_API_BASE_URL (can be set at runtime)
// 2) VITE_API_BASE_URL from env
// 3) Default to empty string (relative URLs) which works with Vite proxy in dev
//    and when SPA is served by backend in production
export const API_BASE =
	(typeof window !== 'undefined' && window.BACKEND_API_BASE_URL) ||
	import.meta.env.VITE_API_BASE_URL ||
	'';