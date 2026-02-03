export default function Welcome({ auth, children }) {
    return (
        <div className="min-h-screen bg-gray-100">
            <nav className="bg-white border-b border-gray-100">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex justify-between h-16">
                        <div className="flex">
                            <div className="shrink-0 flex items-center">
                                <a href="/" className="text-xl font-bold text-gray-800">
                                    ClinLab App
                                </a>
                            </div>
                        </div>
                        <div className="flex items-center">
                            {auth?.user ? (
                                <span className="text-gray-700">Welcome, {auth.user.name}</span>
                            ) : (
                                <div className="space-x-4">
                                    <a href="/login" className="text-gray-700 hover:text-gray-900">
                                        Log in
                                    </a>
                                    <a href="/register" className="text-gray-700 hover:text-gray-900">
                                        Register
                                    </a>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </nav>

            <main className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {children}
                </div>
            </main>
        </div>
    );
}
