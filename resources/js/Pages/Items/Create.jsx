import { useForm, Link } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';

export default function Create({ sections }) {
    const { data, setData, post, processing } = useForm({ section_id: '', label: '', unit: 'pcs', reorder_level: 10 });
    return (
        <AppLayout title="Add New Item">
            <div className="bg-white shadow-sm sm:rounded-lg p-6">
                <form onSubmit={(e) => { e.preventDefault(); post('/items'); }} className="space-y-4 max-w-2xl">
                    <div><label className="block text-sm font-medium">Section *</label><select value={data.section_id} onChange={(e) => setData('section_id', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300" required><option value="">Select</option>{sections.map(s => <option key={s.section_id} value={s.section_id}>{s.label}</option>)}</select></div>
                    <div><label className="block text-sm font-medium">Item Name *</label><input type="text" value={data.label} onChange={(e) => setData('label', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300" required /></div>
                    <div><label className="block text-sm font-medium">Unit</label><input type="text" value={data.unit} onChange={(e) => setData('unit', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300" /></div>
                    <div><label className="block text-sm font-medium">Reorder Level</label><input type="number" value={data.reorder_level} onChange={(e) => setData('reorder_level', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300" /></div>
                    <div className="flex justify-end space-x-4">
                        <Link href="/items" className="px-4 py-2 bg-gray-300 rounded-md text-xs uppercase">Cancel</Link>
                        <button type="submit" disabled={processing} className="px-4 py-2 bg-blue-600 text-white rounded-md text-xs uppercase">Save</button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
