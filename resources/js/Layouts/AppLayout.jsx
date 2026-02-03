import { Link } from '@inertiajs/react';

export default function AppLayout({ children, title }) {
    return (
        <div className="min-h-screen bg-gray-100">
            <nav className="bg-white border-b border-gray-200">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex justify-between h-16">
                        <div className="flex">
                            <div className="shrink-0 flex items-center">
                                <Link href="/" className="text-xl font-bold text-gray-800">
                                    ClinLab App
                                </Link>
                            </div>
                            <div className="hidden space-x-4 sm:-my-px sm:ml-10 sm:flex">
                                <Link href="/patients" className="inline-flex items-center px-1 pt-1 text-sm font-medium text-gray-500 hover:text-gray-700">Patients</Link>
                                <Link href="/physicians" className="inline-flex items-center px-1 pt-1 text-sm font-medium text-gray-500 hover:text-gray-700">Physicians</Link>
                                <Link href="/tests" className="inline-flex items-center px-1 pt-1 text-sm font-medium text-gray-500 hover:text-gray-700">Tests</Link>
                                <Link href="/sections" className="inline-flex items-center px-1 pt-1 text-sm font-medium text-gray-500 hover:text-gray-700">Sections</Link>
                                <Link href="/employees" className="inline-flex items-center px-1 pt-1 text-sm font-medium text-gray-500 hover:text-gray-700">Employees</Link>
                                <Link href="/transactions" className="inline-flex items-center px-1 pt-1 text-sm font-medium text-gray-500 hover:text-gray-700">Transactions</Link>
                                <Link href="/items" className="inline-flex items-center px-1 pt-1 text-sm font-medium text-gray-500 hover:text-gray-700">Inventory</Link>
                                <Link href="/equipment" className="inline-flex items-center px-1 pt-1 text-sm font-medium text-gray-500 hover:text-gray-700">Equipment</Link>
                                <Link href="/activity-logs" className="inline-flex items-center px-1 pt-1 text-sm font-medium text-gray-500 hover:text-gray-700">Activity Logs</Link>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            {title && (
                <header className="bg-white shadow">
                    <div className="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            {title}
                        </h2>
                    </div>
                </header>
            )}

            <main className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {children}
                </div>
            </main>
        </div>
    );
}
