import { Link } from '@inertiajs/react';
import Welcome from '../Layouts/Welcome';

export default function WelcomePage({ auth }) {
    return (
        <Welcome auth={auth}>
            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div className="p-6 text-gray-900">
                    <h1 className="text-3xl font-bold mb-4">Welcome to ClinLab App</h1>
                    <p className="mb-4">
                        You're running Laravel with Inertia.js and React!
                    </p>
                    <div className="flex gap-4">
                        <Link 
                            href="/patients" 
                            className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            View Patients
                        </Link>
                    </div>
                </div>
            </div>
        </Welcome>
    );
}
