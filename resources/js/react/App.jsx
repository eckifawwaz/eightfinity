import React from 'react';
import { createRoot } from 'react-dom/client';
import {
    BrowserRouter,
    Navigate,
    Route,
    Routes,
    useLocation,
    useNavigate,
} from 'react-router-dom';
import AdminBookings from './pages/admin/AdminBookings';
import AdminCustomers from './pages/admin/AdminCustomers';
import AdminDashboard from './pages/admin/AdminDashboard';
import AdminLayoutView from './pages/admin/AdminLayoutView';
import AdminLogin from './pages/admin/AdminLogin';
import AdminLogout from './pages/admin/AdminLogout';
import AdminProfile from './pages/admin/AdminProfile';
import AdminQueue from './pages/admin/AdminQueue';
import UserBook from './pages/user/UserBook';
import UserHome from './pages/user/UserHome';
import UserPackageDetail from './pages/user/UserPackageDetail';
import UserPayment from './pages/user/UserPayment';
import UserPaymentSuccess from './pages/user/UserPaymentSuccess';
import UserProfile from './pages/user/UserProfile';
import UserSignIn from './pages/user/UserSignIn';
import UserSignUp from './pages/user/UserSignUp';
import './styles.css';

function FloatingBackButton() {
    const navigate = useNavigate();
    const location = useLocation();

    function goBack() {
        if (window.history.length > 1) {
            navigate(-1);
            return;
        }

        navigate(location.pathname.startsWith('/admin') ? '/dashboard' : '/home');
    }

    return (
        <button className="floating-back-button" onClick={goBack} type="button">
            ← Back
        </button>
    );
}

function App() {
    return (
        <BrowserRouter>
            <Routes>
                <Route path="/" element={<Navigate to="/register" replace />} />
                <Route path="/login" element={<UserSignIn />} />
                <Route path="/register" element={<UserSignUp />} />
                <Route path="/home" element={<UserHome />} />
                <Route path="/book" element={<UserBook />} />
                <Route path="/packages/:slug" element={<UserPackageDetail />} />
                <Route path="/payment" element={<UserPayment />} />
                <Route path="/payment/success/:booking" element={<UserPaymentSuccess />} />
                <Route path="/profile" element={<UserProfile />} />
                <Route path="/admin/login" element={<AdminLogin />} />
                <Route path="/dashboard" element={<AdminDashboard />} />
                <Route path="/admin/bookings" element={<AdminBookings />} />
                <Route path="/admin/queue" element={<AdminQueue />} />
                <Route path="/admin/customers" element={<AdminCustomers />} />
                <Route path="/admin/layout" element={<AdminLayoutView />} />
                <Route path="/admin/logout" element={<AdminLogout />} />
                <Route path="/admin/profile" element={<AdminProfile />} />
            </Routes>
            <FloatingBackButton />
        </BrowserRouter>
    );
}

createRoot(document.getElementById('root')).render(<App />);
