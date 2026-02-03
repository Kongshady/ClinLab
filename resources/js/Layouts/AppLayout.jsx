import { Link } from '@inertiajs/react';
import { useState } from 'react';

export default function AppLayout({ children, title }) {
    const [userDropdownOpen, setUserDropdownOpen] = useState(false);

    return (
        <div className="min-h-screen bg-gray-100 flex">
            {/* Sidebar */}
            <aside className="w-64 bg-white shadow-lg fixed h-full overflow-y-auto">
                {/* Logo */}
                <div className="p-6 border-b">
                    <Link href="/" className="flex items-center space-x-2">
                        <i className="fas fa-flask text-2xl"></i>
                        <span className="text-xl font-bold">Clinical Lab</span>
                    </Link>
                </div>

                {/* Navigation */}
                <nav className="p-4">
                    <Link href="/patients" className="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-100 transition-colors">
                        <i className="fas fa-user-injured w-5"></i>
                        <span>Patients</span>
                    </Link>
                    <Link href="/physicians" className="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-100 transition-colors">
                        <i className="fas fa-user-md w-5"></i>
                        <span>Physicians</span>
                    </Link>
                    <Link href="/tests" className="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-100 transition-colors">
                        <i className="fas fa-vial w-5"></i>
                        <span>Tests</span>
                    </Link>
                    <Link href="/lab-results" className="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-100 transition-colors">
                        <i className="fas fa-file-medical w-5"></i>
                        <span>Lab Results</span>
                    </Link>
                    <Link href="/sections" className="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-100 transition-colors">
                        <i className="fas fa-sitemap w-5"></i>
                        <span>Sections</span>
                    </Link>
                    <Link href="/employees" className="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-100 transition-colors">
                        <i className="fas fa-users w-5"></i>
                        <span>Employees</span>
                    </Link>
                    <Link href="/transactions" className="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-100 transition-colors">
                        <i className="fas fa-exchange-alt w-5"></i>
                        <span>Transactions</span>
                    </Link>
                    <Link href="/items" className="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-100 transition-colors">
                        <i className="fas fa-box w-5"></i>
                        <span>Items</span>
                    </Link>
                    <Link href="/equipment" className="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-100 transition-colors">
                        <i className="fas fa-microscope w-5"></i>
                        <span>Equipment</span>
                    </Link>
                    <Link href="/activity-logs" className="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-100 transition-colors">
                        <i className="fas fa-clipboard-list w-5"></i>
                        <span>Activity Logs</span>
                    </Link>
                </nav>

                {/* User Section */}
                <div className="absolute bottom-0 w-64 border-t bg-white">
                    <div className="relative">
                        <button
                            onClick={() => setUserDropdownOpen(!userDropdownOpen)}
                            className="w-full flex items-center space-x-3 px-6 py-4 hover:bg-gray-100 transition-colors"
                        >
                            <i className="fas fa-user-circle text-2xl"></i>
                            <div className="flex-1 text-left">
                                <div className="font-medium">Martin Tolang</div>
                                <div className="text-sm text-gray-500">Laboratory Manager</div>
                            </div>
                            <i className={`fas fa-chevron-up transition-transform ${userDropdownOpen ? '' : 'rotate-180'}`}></i>
                        </button>

                        {/* Dropdown Menu */}
                        {userDropdownOpen && (
                            <div className="absolute bottom-full w-full bg-white border-t shadow-lg">
                                <Link href="/profile" className="flex items-center space-x-3 px-6 py-3 hover:bg-gray-100 transition-colors">
                                    <i className="fas fa-user w-5"></i>
                                    <span>Profile</span>
                                </Link>
                                <button className="w-full flex items-center space-x-3 px-6 py-3 hover:bg-gray-100 transition-colors text-left">
                                    <i className="fas fa-sign-out-alt w-5"></i>
                                    <span>Logout</span>
                                </button>
                            </div>
                        )}
                    </div>
                </div>
            </aside>

            {/* Main Content */}
            <div className="flex-1 ml-64">
                {title && (
                    <header className="bg-white shadow">
                        <div className="px-8 py-6">
                            <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                                {title}
                            </h2>
                        </div>
                    </header>
                )}

                <main className="py-8">
                    <div className="px-8">
                        {children}
                    </div>
                </main>
            </div>
        </div>
    );
}
