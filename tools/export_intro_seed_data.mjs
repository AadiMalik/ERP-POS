/**
 * Export Intro website demo content (excluding packages) to JSON for Laravel seeder.
 * Run from erp-intro: node ../erp/tools/export_intro_seed_data.mjs
 * Or from erp: node tools/export_intro_seed_data.mjs
 */
import { createRequire } from 'module'
import fs from 'fs'
import path from 'path'
import { fileURLToPath, pathToFileURL } from 'url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const introRoot = path.resolve(__dirname, '../../erp-intro')
const outDir = path.resolve(__dirname, '../database/seeders/data')
const outFile = path.join(outDir, 'intro_website_content.json')

async function load(rel) {
  const full = path.join(introRoot, 'src/data', rel)
  return import(pathToFileURL(full).href)
}

const demo = await load('demo.js')
const catalog = await load('catalog.js')
const site = await load('site.js')
const comments = await load('comments.js')

const payload = {
  // packages intentionally omitted — use ERP packages table
  testimonials: demo.testimonials,
  bankDetails: demo.bankDetails,
  journeySteps: demo.journeySteps,
  blogCategories: demo.blogCategories,
  blogAuthors: demo.blogAuthors,
  blogPosts: demo.blogPosts,
  seedComments: comments.seedComments,
  site: site.site,
  navLinks: site.navLinks,
  tickerItems: catalog.tickerItems,
  railModules: catalog.railModules,
  explorerGroups: catalog.explorerGroups,
  explorerCards: catalog.explorerCards,
  orbitNodes: catalog.orbitNodes,
  deckItems: catalog.deckItems,
  businessTypes: catalog.businessTypes,
  homeFaqs: catalog.homeFaqs,
  pricingFaqs: catalog.pricingFaqs,
}

fs.mkdirSync(outDir, { recursive: true })
fs.writeFileSync(outFile, JSON.stringify(payload, null, 2), 'utf8')
console.log('Wrote', outFile)
console.log('Posts:', payload.blogPosts.length, 'Testimonials:', payload.testimonials.length, 'Modules:', payload.railModules.length)
