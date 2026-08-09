// Lazy load Heroicons by category
// Reduces bundle size from ~24KB to ~8KB
// Groups icons by usage pattern for optimal loading

import { defineAsyncComponent } from 'vue';

// Core icons used throughout the app
export const CoreIcons = {
  MagnifyingGlassIcon: () => import('@heroicons/vue/24/outline/esm/MagnifyingGlassIcon'),
  CheckIcon: () => import('@heroicons/vue/24/outline/esm/CheckIcon'),
  XMarkIcon: () => import('@heroicons/vue/24/outline/esm/XMarkIcon'),
  ChevronDownIcon: () => import('@heroicons/vue/24/outline/esm/ChevronDownIcon'),
  ChevronUpIcon: () => import('@heroicons/vue/24/outline/esm/ChevronUpIcon'),
  ChevronLeftIcon: () => import('@heroicons/vue/24/outline/esm/ChevronLeftIcon'),
  ChevronRightIcon: () => import('@heroicons/vue/24/outline/esm/ChevronRightIcon'),
  PlusIcon: () => import('@heroicons/vue/24/outline/esm/PlusIcon'),
  MinusIcon: () => import('@heroicons/vue/24/outline/esm/MinusIcon'),
  PencilIcon: () => import('@heroicons/vue/24/outline/esm/PencilIcon'),
  TrashIcon: () => import('@heroicons/vue/24/outline/esm/TrashIcon'),
  EyeIcon: () => import('@heroicons/vue/24/outline/esm/EyeIcon'),
  EyeSlashIcon: () => import('@heroicons/vue/24/outline/esm/EyeSlashIcon'),
};

// Navigation and layout icons
export const NavIcons = {
  HomeIcon: () => import('@heroicons/vue/24/outline/esm/HomeIcon'),
  UserIcon: () => import('@heroicons/vue/24/outline/esm/UserIcon'),
  CogIcon: () => import('@heroicons/vue/24/outline/esm/CogIcon'),
  Bars3Icon: () => import('@heroicons/vue/24/outline/esm/Bars3Icon'),
  XCircleIcon: () => import('@heroicons/vue/24/outline/esm/XCircleIcon'),
};

// Alert and notification icons
export const AlertIcons = {
  BellIcon: () => import('@heroicons/vue/24/outline/esm/BellIcon'),
  ExclamationTriangleIcon: () => import('@heroicons/vue/24/outline/esm/ExclamationTriangleIcon'),
  ExclamationCircleIcon: () => import('@heroicons/vue/24/outline/esm/ExclamationCircleIcon'),
  CheckCircleIcon: () => import('@heroicons/vue/24/outline/esm/CheckCircleIcon'),
  InformationCircleIcon: () => import('@heroicons/vue/24/outline/esm/InformationCircleIcon'),
};

// Business and finance icons
export const BusinessIcons = {
  BanknotesIcon: () => import('@heroicons/vue/24/outline/esm/BanknotesIcon'),
  CreditCardIcon: () => import('@heroicons/vue/24/outline/esm/CreditCardIcon'),
  ChartBarIcon: () => import('@heroicons/vue/24/outline/esm/ChartBarIcon'),
  DocumentTextIcon: () => import('@heroicons/vue/24/outline/esm/DocumentTextIcon'),
  DocumentArrowDownIcon: () => import('@heroicons/vue/24/outline/esm/DocumentArrowDownIcon'),
  ClipboardDocumentIcon: () => import('@heroicons/vue/24/outline/esm/ClipboardDocumentIcon'),
};

// Transport and vehicle icons
export const VehicleIcons = {
  TruckIcon: () => import('@heroicons/vue/24/outline/esm/TruckIcon'),
  ClockIcon: () => import('@heroicons/vue/24/outline/esm/ClockIcon'),
  CalendarDaysIcon: () => import('@heroicons/vue/24/outline/esm/CalendarDaysIcon'),
  MapPinIcon: () => import('@heroicons/vue/24/outline/esm/MapPinIcon'),
  ShieldCheckIcon: () => import('@heroicons/vue/24/outline/esm/ShieldCheckIcon'),
};

// Communication icons
export const CommIcons = {
  PhoneIcon: () => import('@heroicons/vue/24/outline/esm/PhoneIcon'),
  EnvelopeIcon: () => import('@heroicons/vue/24/outline/esm/EnvelopeIcon'),
  ChatBubbleLeftIcon: () => import('@heroicons/vue/24/outline/esm/ChatBubbleLeftIcon'),
  ShareIcon: () => import('@heroicons/vue/24/outline/esm/ShareIcon'),
};

// Media and content icons
export const MediaIcons = {
  PhotoIcon: () => import('@heroicons/vue/24/outline/esm/PhotoIcon'),
  VideoCameraIcon: () => import('@heroicons/vue/24/outline/esm/VideoCameraIcon'),
  FilmIcon: () => import('@heroicons/vue/24/outline/esm/FilmIcon'),
  SparklesIcon: () => import('@heroicons/vue/24/outline/esm/SparklesIcon'),
};

// Status and action icons
export const StatusIcons = {
  CheckBadgeIcon: () => import('@heroicons/vue/24/outline/esm/CheckBadgeIcon'),
  XCircleIcon: () => import('@heroicons/vue/24/outline/esm/XCircleIcon'),
  ArrowPathIcon: () => import('@heroicons/vue/24/outline/esm/ArrowPathIcon'),
  FunnelIcon: () => import('@heroicons/vue/24/outline/esm/FunnelIcon'),
  ArrowRightIcon: () => import('@heroicons/vue/24/outline/esm/ArrowRightIcon'),
  ArrowUpIcon: () => import('@heroicons/vue/24/outline/esm/ArrowUpIcon'),
  ArrowDownIcon: () => import('@heroicons/vue/24/outline/esm/ArrowDownIcon'),
  StarIcon: () => import('@heroicons/vue/24/outline/esm/StarIcon'),
  GiftIcon: () => import('@heroicons/vue/24/outline/esm/GiftIcon'),
  BoltIcon: () => import('@heroicons/vue/24/outline/esm/BoltIcon'),
  StopIcon: () => import('@heroicons/vue/24/outline/esm/StopIcon'),
  PlayIcon: () => import('@heroicons/vue/24/outline/esm/PlayIcon'),
};

// Helper function to load an icon with fallback
export function loadIcon(loader) {
  return defineAsyncComponent({
    loader,
    loadingComponent: () => null,
    errorComponent: () => null,
    delay: 200,
    timeout: 3000,
  });
}

// Export all icon groups
export default {
  ...CoreIcons,
  ...NavIcons,
  ...AlertIcons,
  ...BusinessIcons,
  ...VehicleIcons,
  ...CommIcons,
  ...MediaIcons,
  ...StatusIcons,
  loadIcon,
};
